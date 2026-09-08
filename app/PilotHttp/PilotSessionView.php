<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;

final class PilotSessionView
{
    public static function login(string $csrf):string
    {
        return '<!doctype html><form><input name="csrfToken" value="'.PilotView::e($csrf).'"></form>';
    }

    public static function withInvitationFeedback(string $document,string $url):string
    {
        $feedback='<section class="fm2-invite-feedback"><strong>Приглашение готово</strong><p>Передайте ссылку пользователю. Она действует 24 часа и используется один раз. При потере ссылки перевыпустите приглашение в списке пользователей.</p><label class="shlz-field"><span class="shlz-field__label">Ссылка активации</span><span class="shlz-field__control"><input class="shlz-input" readonly value="'.PilotView::e($url).'"></span></label></section>';
        return \str_replace('<section class="fm2-directory-summary fm2-user-summary">',$feedback.'<section class="fm2-directory-summary fm2-user-summary">',$document);
    }
    public static function withInvitationError(string $document):string
    {
        $feedback='<section class="fm2-invite-feedback fm2-invite-feedback--error" role="alert"><strong>Приглашение не выдано</strong><p>Проверьте данные пользователя. Если он уже есть в списке и ожидает активации, используйте перевыпуск. Для активных и заблокированных пользователей он недоступен.</p></section>';
        return \str_replace('<section class="fm2-directory-summary fm2-user-summary">',$feedback.'<section class="fm2-directory-summary fm2-user-summary">',$document);
    }
}
