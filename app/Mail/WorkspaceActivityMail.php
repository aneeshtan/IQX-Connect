<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkspaceActivityMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $workspaceName,
        public string $title,
        public string $body,
        public string $recordReference,
        public string $recordLabel,
        public ?User $actor = null,
        public ?string $actionUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->workspaceName}: {$this->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.workspace-activity',
        );
    }
}
