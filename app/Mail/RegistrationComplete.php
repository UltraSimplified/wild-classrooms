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

class RegistrationComplete extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $heading,
        public Group $group,
        public User $member,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.registration-complete');
    }
}
