<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to staff when a group registers.
 *
 * The registering user is passed in rather than read from Auth inside the
 * view: these are queueable, and a queued job has no session to read.
 */
class GroupRegistered extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $heading,
        public Group $group,
        public User $registeredBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.group-registered');
    }
}
