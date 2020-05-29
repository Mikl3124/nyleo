<?php

namespace App\Notifications;

use App\Model\User;
use App\Model\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MessageNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $message; 

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Message $message, User $user)
    {

        $this->message = $message;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     * 
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'messageContent' => $this->message->content,
            'messageId' => $this->message->id,
            'email' => $this->user->email,
            'senderId' => $this->user->id
        ];
    }
}
