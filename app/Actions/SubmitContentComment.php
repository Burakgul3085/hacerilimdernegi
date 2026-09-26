<?php

namespace App\Actions;

use App\Enums\PostCommentStatus;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\MediaAlbum;
use App\Models\Post;
use App\Models\PostComment;
use App\Support\FormGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubmitContentComment
{
    public function __construct(private ProcessPostComment $process) {}

    public function handle(Request $request, Model $subject): RedirectResponse
    {
        abort_unless($this->isVisible($subject), 404);

        if (FormGuard::isBot($request)) {
            return $this->redirect($subject, 'Yorumunuz alındı. Yayınlanması için yönetici onayı bekleniyor.');
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:180'],
            'body' => ['required', 'string', 'max:2000'],
            'hide_name' => ['sometimes', 'boolean'],
            'kvkk_accepted' => ['accepted'],
        ], [
            'first_name.required' => 'Ad gerekli.',
            'last_name.required' => 'Soyad gerekli.',
            'email.required' => 'E-posta gerekli.',
            'email.email' => 'Geçerli bir e-posta yazın.',
            'body.required' => 'Yorum gerekli.',
            'body.max' => 'Yorum en fazla 2000 karakter olabilir.',
            'kvkk_accepted.accepted' => 'KVKK aydınlatma metnini kabul etmelisiniz.',
        ]);

        if ($validator->fails()) {
            return $this->redirect($subject)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $firstName = PostComment::plainName($data['first_name']);
        $lastName = PostComment::plainName($data['last_name']);
        $body = PostComment::plainBody($data['body']);
        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'Ad gerekli.';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'Soyad gerekli.';
        }

        if ($body === '') {
            $errors['body'] = 'Yorum gerekli.';
        }

        if ($errors !== []) {
            return $this->redirect($subject)
                ->withErrors($errors)
                ->withInput();
        }

        $comment = $subject->comments()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $data['email'],
            'body' => $body,
            'hide_name' => $request->boolean('hide_name'),
            'status' => PostCommentStatus::Pending,
            'ip_address' => $request->ip(),
        ]);

        $this->process->handle($comment);

        return $this->redirect($subject, 'Yorumunuz alındı. Yayınlanması için yönetici onayı bekleniyor.');
    }

    private function isVisible(Model $subject): bool
    {
        return match (true) {
            $subject instanceof Post => $subject->isVisibleOnSite(),
            $subject instanceof Activity => (bool) $subject->is_published,
            $subject instanceof Announcement => $subject->isVisibleOnSite(),
            $subject instanceof MediaAlbum => $subject->isVisibleOnSite(),
            default => false,
        };
    }

    private function redirect(Model $subject, ?string $message = null): RedirectResponse
    {
        $redirect = redirect()->to($this->pageUrl($subject).'#yorumlar');

        if ($message === null) {
            return $redirect;
        }

        return $redirect
            ->with('status', $message)
            ->with('status_context', 'content-comment');
    }

    private function pageUrl(Model $subject): string
    {
        return match (true) {
            $subject instanceof Post => route('posts.show', $subject),
            $subject instanceof Activity => route('activities.show', $subject),
            $subject instanceof Announcement => route('announcements.show', $subject),
            $subject instanceof MediaAlbum => $subject->publicUrl(),
            default => url('/'),
        };
    }
}
