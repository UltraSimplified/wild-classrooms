<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Challenge;
use App\Models\Group;
use App\Models\Spotting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to staff when a spotting is uploaded, so it can be moderated.
 */
class SpottingRecorded extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $spotter;

    public string $what3words;

    public function __construct(
        public string $heading,
        public Challenge $challenge,
        public Spotting $spotting,
        public User $uploadedBy,
        Group $group,
    ) {
        $this->spotter = (string) $group->title;
        // Stored with a leading /// which reads as a path in a mail client.
        $this->what3words = str_replace('///', '', (string) $spotting->what3words);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.spotting-recorded');
    }
}
