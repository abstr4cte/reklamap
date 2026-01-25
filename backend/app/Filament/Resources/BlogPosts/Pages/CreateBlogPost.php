<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use App\Models\Newsletter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        // Send newsletter email only if blog post is published
        $blogPost = $this->record;
        
        if ($blogPost->status === 'published') {
            $subscribers = Newsletter::all();

            if ($subscribers->isNotEmpty()) {
                foreach ($subscribers as $subscriber) {
                    Mail::send('emails.blog-notification', [
                        'blogPost' => $blogPost,
                        'blogUrl' => config('app.frontend_url') . '/blog/' . $blogPost->slug,
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
