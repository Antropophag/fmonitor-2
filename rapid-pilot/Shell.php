<?php

declare(strict_types=1);

final class RapidPilotShell
{
    public static function decorate(string $html, string $csrf, bool $calendarActive, bool $otizAllowed, bool $otizActive): string
    {
        if ($otizAllowed) $html = RapidPilotOtiz::decorateNavigation($html, $otizActive);
        $html = RapidPilotCalendar::decorateNavigation($html, $calendarActive);

        $token = htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoutIcon = '<svg class="fm2-user-logout-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="/pilot/assets/shlz-icons.svg#shlz-icon-logout"/></svg>';
        $logout = '<form method="post" action="/pilot/logout" class="fm2-logout-form"><input type="hidden" name="csrfToken" value="' . $token . '"><button class="fm2-logout" type="submit">' . $logoutIcon . '<span class="fm2-logout-text">Выйти</span></button></form>';

        return str_replace('<!-- fm2-user-actions -->', $logout, $html);
    }
}
