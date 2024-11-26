<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

/**
 * [Internal] Protects a given stream from actually closing and only discards its incoming data instead.
 *
 * This is used internally to prevent the underlying connection from closing, so
 * that we can still send back a response over the same stream.
 *
 * @internal
 * */
class CloseProtectionStream extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    private $closed = false;
    private $paused = false;

    /**
     * @param ReadableStreamInterface $input stream that will be discarded instead of closing it on an 'close' event.
     */
    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

        $this->input->on('data', [$this, 'handleData']);
        $this->input->on('end', [$this, 'handleEnd']);
        $this->input->on('error', [$this, 'handleError']);
        $this->input->on('close', [$this, 'close']);
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function pause()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = true;
        $this->input->pause();
    }

    public function resume()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = false;
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        // stop listening for incoming events
        $this->input->removeListener('data', [$this, 'handleData']);
        $this->input->removeListener('error', [$this, 'handleError']);
        $this->input->removeListener('end', [$this, 'handleEnd']);
        $this->input->removeListener('close', [$this, 'close']);

        // resume the stream to ensure we discard everything from incoming connection
        if ($this->paused) {
            $this->paused = false;
            $this->input->resume();
        }

        $this->emit('close');
        $this->removeAllListeners();
    }

    /** @internal */
    public function handleData($data)
    {
        $this->emit('data', [$data]);
    }

    /** @internal */
    public function handleEnd()
    {
        $this->emit('end');
        $this->close();
    }

    /** @internal */
    public function handleError(\Exception $e)
    {
        $this->emit('error', [$e]);
    }
}
