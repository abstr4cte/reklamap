<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\Newsletter;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterSave(): void
    {
        // Send newsletter email if status changed to published
        $blogPost = $this->record;
        $originalStatus = $blogPost->getOriginal('status');

        if ($originalStatus !== 'published' && $blogPost->status === 'published') {
            $subscribers = Newsletter::all();

            if ($subscribers->isNotEmpty()) {
                foreach ($subscribers as $subscriber) {
                    Mail::send('emails.blog-notification', [
                        'blogPost' => $blogPost,
                        'blogUrl' => config('app.frontend_url') . '/blog/' . $blogPost->slug,
                        'unsubscribeToken' => $subscriber->unsubscribe_token,
                    ], function ($message) use ($subscriber) {

                        $message->to($subscriber->email)
                            ->subject('Nowy artykuł na blogu Reklamap');
                    });
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
