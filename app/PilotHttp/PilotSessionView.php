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
        $feedback='<section class="fm2-invite-feedback"><input value="'.PilotView::e($url).'"></section>';
        return \str_replace('<section class="fm2-directory-summary fm2-user-summary">',$feedback.'<section class="fm2-directory-summary fm2-user-summary">',$document);
    }
}
