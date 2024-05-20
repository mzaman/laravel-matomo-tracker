<?php

namespace MasudZaman\MatomoTracker;

use Exception;
use \Illuminate\Http\Request;
use \MatomoTracker;
use MasudZaman\MatomoTracker\Jobs\QueueEvent;

class LaravelMatomoTracker extends MatomoTracker
{
    /** @var bool */
    protected $async;
    /** @var string */
    protected $apiUrl;
    /** @var int */
    public $idSite;
    /** @var string */
    protected $tokenAuth;
    /** @var string */
    protected $queue;
    /** @var string */
    protected $queueConnection;

    /**
     * MatomoTracker constructor.
     *
     * @param int|null $idSite
     * @param string|null $apiUrl
     */
    public function __construct($idSite = null, $apiUrl = null)
    {
        $idSite = $idSite ?? config('matomotracker.idSite');
        $apiUrl = $apiUrl ?? config('matomotracker.url');
        $this->async = config('matomotracker.async', false);
        $this->queue = config('matomotracker.queue', 'default');
        $this->queueConnection = config('matomotracker.queueConnection', 'database');

        parent::__construct((int)$idSite, $apiUrl);

        $this->setTokenAuth(config('matomotracker.tokenAuth'));
        $this->eventCustomVar = false; // default: []
        $this->forcedDatetime = time(); // default: false. 
        // force-set time, so queued commands use the right request time
        $this->pageCustomVar = false; // default: []

        $this->visitCount = 0;
        $this->currentVisitTs = false;
        $this->lastVisitTs = false;
        $this->ecommerceLastOrderTimestamp = false;

    }

    /**
     * Sets the queue name
     *
     * @param string $queueName
     *
     * @return $this
     */
    public function setQueue(string $queueName)
    {
        $this->queue = $queueName;
        return $this;
    }

    // Legacy code
    // /**
    //  * Sets a custom dimension
    //  *
    //  * @param int $customDimensionId
    //  * @param string $value
    //  *
    //  * @return $this
    //  */
    // public function setCustomDimension($id, $value)
    // {
    //     $this->setCustomTrackingParameter('dimension' . $id, $value);
    //     return $this;
    // }

    /**
     * Sets some custom dimensions
     *
     * @param array $customDimensions Is an array of objects with the fields 'id' and 'value'
     *
     * @return $this
     */
    public function setCustomDimensions(array $customDimensions)
    {
        foreach ($customDimensions as $key => $customDimension) {
            $this->checkCustomDimension($customDimension);
            $this->setCustomDimension($key, $customDimension);
        }

        return $this;
    }

    /** checks if custom dimension data is correct
     *
     * @param object $customDimension
     *
     * @return bool
     */
    private function checkCustomDimension(object $customDimension): bool
    {

        if (gettype($customDimension) !== 'object') {
            throw new Exception('Key is not of type object in custom dimension.');
        }

        if (property_exists($customDimension, 'id')) {
            if (gettype($customDimension->id) !== 'integer') {
                throw new Exception('Id is not of type integer in custom dimension.');
            }
        } else {
            throw new Exception('Missing property \'id\' in custom dimension.');
        }

        if (property_exists($customDimension, 'value')) {
            if (gettype($customDimension->value) !== 'string') {
                throw new Exception('Value is not of type string in custom dimension.');
            }
        } else {
            throw new Exception('Missing property \'id\' in custom dimension.');
        }

        return true;
    }

    /**
     * Sets some custom variables
     *
     * @param array $customVariables
     */
    public function setCustomVariables(array $customVariables)
    {
        foreach ($customVariables as $customVariable) {
            $this->checkCustomVariable($customVariable);

            $this->setCustomVariable($customVariable->id, $customVariable->name, $customVariable->value, property_exists($customVariable, 'scope') ? $customVariable->scope : 'visit');
        }

        return $this;
    }

    /** checks if custom variable data is correct
     *
     * @param object $customVariable
     *
     * @return bool
     */
    private function checkCustomVariable(object $customVariable): bool
    {
        if (gettype($customVariable) !== 'object') {
            throw new Exception('Key is not of type object in custom variable.');
        }

        if (property_exists($customVariable, 'id')) {
            if (gettype($customVariable->id) !== 'integer') {
                throw new Exception('Id is not of type integer in custom variable.');
            }
        } else {
            throw new Exception('Missing property \'id\' in custom variable.');
        }

        if (property_exists($customVariable, 'name')) {
            if (gettype($customVariable->name) !== 'string') {
                throw new Exception('Name is not of type string in custom variable.');
            }
        } else {
            throw new Exception('Missing property \'id\' in custom variable.');
        }

        if (property_exists($customVariable, 'value')) {
            if (gettype($customVariable->value) !== 'string') {
                throw new Exception('Value is not of type string in custom variable.');
            }
        } else {
            throw new Exception('Missing property \'id\' in custom variable.');
        }

        if (property_exists($customVariable, 'scope')) {
            if (gettype($customVariable->scope) !== 'string') {
                throw new Exception('Scope is not of type string in custom variable.');
            }

            if (!array_search($customVariable->scope, ['visit', 'page'])) {
                throw new Exception('Scope is not valid in custom variable. Use either \'visit\' or \'page\'');
            }
        }

        return true;
    }

    /** Shorthand for doTrackAction($actionUrl, 'download')
     *
     * @param string $actionUrl
     *
     * @return mixed
     */
    public function doTrackDownload(string $actionUrl)
    {
        return $this->doTrackAction($actionUrl, 'download');
    }

    /** Shorthand for doTrackAction($actionUrl, 'link')
     *
     * @param string $actionUrl
     *
     * @return mixed
     */
    public function doTrackOutlink(string $actionUrl)
    {
        return $this->doTrackAction($actionUrl, 'link');
    }

    /** Queues a pageview
     *
     * @param string $documentTitle
     *
     * @return void
     */
    public function queuePageView(string $documentTitle)
    {
        dispatch(function () use ($documentTitle) {
            $this->doTrackPageView($documentTitle);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /**
     * Queues or tracks an event based on the asynchronous flag.
     *
     * @param string $category
     * @param string $action
     * @param string|bool $name
     * @param int $value
     * @return void
     */
    public function trackEvent(string $category, string $action, $name = false, $value = 0)
    {
        try {
            if ($this->async) {
                // Queue the event to be tracked asynchronously
                $job = new QueueEvent($category, $action, $name, $value);
                dispatch($job)
                    ->onConnection($this->queueConnection)
                    ->onQueue($this->queue);
            } else {
                // Track the event synchronously
                $this->doTrackEvent($category, $action, $name, $value);
            }
        } catch (\Exception $e) {
            // Handle the exception (e.g., log the error)
            \Log::info("Error processing event: " . $e->getMessage());
            throw $e;
        }
    }

    /** Queues a content impression
     *
     * @param string $contentName
     * @param string $contentPiece
     * @param string|bool $contentTarget
     *
     * @return void
     */
    public function queueContentImpression(string $contentName, string $contentPiece = 'Unknown', $contentTarget = false)
    {
        dispatch(function () use ($contentName, $contentPiece, $contentTarget) {
            $this->doTrackContentImpression($contentName, $contentPiece, $contentTarget);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a content interaction
     *
     * @param string $interaction Like 'click' or 'copy'
     * @param string $contentName
     * @param string $contentPiece
     * @param string|bool $contentTarget
     *
     * @return void
     */
    public function queueContentInteraction(string $interaction, string $contentName, string $contentPiece = 'Unknown', $contentTarget = false)
    {
        dispatch(function () use ($interaction, $contentName, $contentPiece, $contentTarget) {
            $this->doTrackContentInteraction($interaction, $contentName, $contentPiece, $contentTarget);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a site search
     *
     * @param string $keyword
     * @param string $category
     * @param int|bool $countResults
     *
     * @return void
     */
    public function queueSiteSearch(string $keyword, string $category = '',  $countResults = false)
    {
        dispatch(function () use ($keyword, $category, $countResults) {
            $this->doTrackSiteSearch($keyword, $category, $countResults);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a goal
     *
     * @param mixed $idGoal
     * @param float $revencue
     *
     * @return void
     */
    public function queueGoal($idGoal, $revencue = 0.0)
    {
        dispatch(function () use ($idGoal, $revencue) {
            $this->doTrackGoal($idGoal, $revencue);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a download
     *
     * @param string $actionUrl
     *
     * @return void
     */
    public function queueDownload(string $actionUrl)
    {
        dispatch(function () use ($actionUrl) {
            $this->doTrackDownload($actionUrl);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a outlink
     *
     * @param string $actionUrl
     *
     * @return void
     */
    public function queueOutlink(string $actionUrl)
    {
        dispatch(function () use ($actionUrl) {
            $this->doTrackOutlink($actionUrl);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues an ecommerce update
     *
     * @param float $grandTotal
     *
     * @return void
     */
    public function queueEcommerceCartUpdate(float $grandTotal)
    {
        dispatch(function () use ($grandTotal) {
            $this->doTrackEcommerceCartUpdate($grandTotal);
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a ecommerce order
     *
     * @param float $orderId
     * @param float $grandTotal
     * @param float $subTotal
     * @param float $tax
     * @param float $shipping
     * @param float $discount
     *
     * @return void
     */
    public function queueEcommerceOrder(
        float $orderId,
        float $grandTotal,
        float $subTotal = 0.0,
        float $tax = 0.0,
        float $shipping = 0.0,
        float $discount = 0.0
    ) {
        dispatch(function () use (
            $orderId,
            $grandTotal,
            $subTotal,
            $tax,
            $shipping,
            $discount
        ) {
            $this->doTrackEcommerceOrder(
                $orderId,
                $grandTotal,
                $subTotal,
                $tax,
                $shipping,
                $discount
            );
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }

    /** Queues a bulk track
     *
     * @return void
     */
    public function queueBulkTrack()
    {
        dispatch(function () {
            $this->doBulkTrack();
        })
            ->onConnection($this->queueConnection)
            ->onQueue($this->queue);
    }
    public function __sleep() {
        return []; //Pass the names of the variables that should be serialised here
    }
    /**
     * Called after unserializing (e.g. after popping from the queue). Re-set
     * self::$URL, as only non-static properties have been applied.
     */
    public function __wakeup()
    {
        if (!empty($this->apiUrl)) {
            self::$URL = $this->apiUrl;
        }
    }
}
