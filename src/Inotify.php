<?php

namespace Empress\Inotify;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;

/**
 * Non-blocking wrapper for ext-inotify.
 *
 * @author Jakub Groncki <jakub.groncki@gmail.com>
 */
class Inotify
{

    /**
     * @var resource
     */
    private $resource;

    private string $watcherId;

    private ?Deferred $notifier = null;

    public function __construct()
    {
        if (!\function_exists('inotify_init')) {
            throw new InotifyError('The inotify extension must be installed and enabled.');
        }

        if (($handle = \inotify_init()) === false) {
            throw new InotifyError('Could not create inotify handle.');
        }

        $this->resource = $handle;

        \stream_set_blocking($this->resource, false);

        $this->watcherId = Loop::onReadable($this->resource, function ($watcherId) {
            $deferred = $this->notifier;
            $this->notifier = null;

            \assert($deferred !== null);

            $deferred->resolve($this->getEvents());

            /** @psalm-suppress RedundantCondition */
            if (!$this->notifier) {
                Loop::disable($watcherId);
            }
        });

        Loop::disable($this->watcherId);
    }

    public function __destruct()
    {
        Loop::cancel($this->watcherId);

        $this->resource = null;

        if ($this->notifier) {
            $this->notifier->resolve();
            $this->notifier = null;
        }
    }

    /**
     * Reads all pending events.
     *
     * @return Promise<Event[]>
     */
    public function readEvents(): Promise
    {
        if ($this->notifier) {
            throw new PendingEventReadError();
        }

        $events = $this->getEvents();

        if ($events !== []) {
            return new Success($events);
        }

        $this->notifier = new Deferred();

        Loop::enable($this->watcherId);

        return $this->notifier->promise();
    }


    /**
     * Adds a watch and returns the corresponding descriptor.
     *
     * @throws InvalidDescriptorException If watch couldn't be added
     */
    public function addWatch(string $path, int $mask): int
    {
        if (!($descriptor = @\inotify_add_watch($this->resource, $path, $mask))) {
            throw new InvalidDescriptorException('Watch for path "' . $path . '" and mask "' . $mask . '" could not be added.');
        }

        return $descriptor;
    }

    /**
     * Removes a watch.
     *
     * @throws InvalidDescriptorException If watch doesn't exist or couldn't be removed
     */
    public function removeWatch(int $descriptor): void
    {
        if (!@\inotify_rm_watch($this->resource, $descriptor)) {
            throw new InvalidDescriptorException('Watch could not be removed');
        }
    }

    /**
     * Checks if there are pending events.
     */
    public function getPendingEvents(): bool
    {
        return \inotify_queue_len($this->resource) !== 0;
    }

    /**
     * Returns the underlying inotify instance.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return Event[]
     */
    private function getEvents(): array
    {
        $results = \inotify_read($this->resource);

        if ($results === false) {
            return [];
        }

        return \array_map(function (array $result) {
            return Event::create($result);
        }, $results);
    }
}
