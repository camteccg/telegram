<?php

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class TelegramFile.
 */
class TelegramFile extends TelegramBase implements TelegramSenderContract
{
    /** @var string content type. */
    public string $type = 'document';

    public function __construct(string $content = '')
    {
        parent::__construct();
        $this->content($content);
        $this->parseMode(ParseMode::Markdown);
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @return $this
     */
    public function content(string $content): self
    {
        $this->payload['caption'] = $content;

        return $this;
    }

    /**
     * Add File to Message.
     *
     * Generic method to attach files of any type based on API.
     *
     * @param  resource|StreamInterface|string  $file
     * @return $this
     */
    public function file(mixed $file, string $type, ?string $filename = null): self
    {
        $this->type = $type;

        if (is_string($file) && ! $this->isReadableFile($file)) {
            $this->payload[$type] = $file;

            return $this;
        }

        $this->payload['file'] = [
            'name' => $type,
            'contents' => is_resource($file) ? $file : fopen($file, 'rb'),
        ];

        if ($filename !== null) {
            $this->payload['file']['filename'] = $filename;
        }

        return $this;
    }

    /**
     * Attach an image.
     *
     * @return $this
     */
    public function photo(string $file): self
    {
        return $this->file($file, 'photo');
    }

    /**
     * Attach an audio file.
     *
     * @return $this
     */
    public function audio(string $file): self
    {
        return $this->file($file, 'audio');
    }

    /**
     * Attach a document or any file as document.
     *
     * @return $this
     */
    public function document(string $file, ?string $filename = null): self
    {
        return $this->file($file, 'document', $filename);
    }

    /**
     * Attach a video file.
     *
     * @return $this
     */
    public function video(string $file): self
    {
        return $this->file($file, 'video');
    }

    /**
     * Attach an animation file.
     *
     * @return $this
     */
    public function animation(string $file): self
    {
        return $this->file($file, 'animation');
    }

    /**
     * Attach a voice file.
     *
     * @return $this
     */
    public function voice(string $file): self
    {
        return $this->file($file, 'voice');
    }

    /**
     * Attach a video note file.
     *
     * @return $this
     */
    public function videoNote(string $file): self
    {
        return $this->file($file, 'video_note');
    }

    /**
     * Attach a view file as the content for the notification.
     * Supports Laravel blade template.
     *
     * @return $this
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }

    /**
     * Determine there is a file.
     */
    public function hasFile(): bool
    {
        return isset($this->payload['file']);
    }

    /**
     * Returns params payload.
     */
    public function toArray(): array
    {
        return $this->hasFile() ? $this->toMultipart() : $this->payload;
    }

    /**
     * Create Multipart array.
     */
    public function toMultipart(): array
    {
        $data = [];
        foreach ($this->payload as $name => $contents) {
            $data[] = ($name === 'file') ? $contents : compact('name', 'contents');
        }

        return $data;
    }

    /**
     * @throws CouldNotSendNotification
     */
    public function send(): ?ResponseInterface
    {
        return $this->telegram->sendFile($this->toArray(), $this->type, $this->hasFile());
    }

    /**
     * Determine if it's a regular and readable file.
     */
    protected function isReadableFile(string $file): bool
    {
        return is_file($file) && is_readable($file);
    }
}
