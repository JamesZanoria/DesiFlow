@extends('layouts.app')

@section('title', 'DZAI Intelligence Studio')

@section('content')
    @php
        use Illuminate\Support\Carbon;
        use Illuminate\Support\Facades\Route;

        $userName   = auth()->user()->name;
        $firstName  = explode(' ', trim($userName))[0];
        $initials   = collect(explode(' ', $userName))->map(fn($p) => strtoupper($p[0] ?? ''))->take(2)->implode('');
        $activeTitle = optional(collect($conversations)->firstWhere('id', $activeConversationId))->title ?? 'New chat';

        $canRename = Route::has('admin.assistant.rename');
        $canDelete = Route::has('admin.assistant.destroy');

        // Group history by recency
        $groups = ['Today' => [], 'Yesterday' => [], 'Previous 7 days' => [], 'Earlier' => []];
        foreach ($conversations as $conv) {
            $d = Carbon::parse($conv->updated_at ?? $conv->created_at ?? now());
            if ($d->isToday())            { $k = 'Today'; }
            elseif ($d->isYesterday())    { $k = 'Yesterday'; }
            elseif ($d->gte(now()->subDays(7)->startOfDay())) { $k = 'Previous 7 days'; }
            else                          { $k = 'Earlier'; }
            $groups[$k][] = $conv;
        }

        $config = [
            'endpoint'       => route('admin.assistant.ask'),
            'conversationId' => $activeConversationId,
            'csrf'           => csrf_token(),
        ];
    @endphp

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Icon sprite --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
        <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 4.5v15m7.5-7.5h-15"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></symbol>
        <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5"/></symbol>
        <symbol id="i-sparkles" viewBox="0 0 24 24"><path d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/></symbol>
        <symbol id="i-up" viewBox="0 0 24 24"><path d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"/></symbol>
        <symbol id="i-stop" viewBox="0 0 24 24"><rect x="6.5" y="6.5" width="11" height="11" rx="2.5" fill="currentColor" stroke="none"/></symbol>
        <symbol id="i-clip" viewBox="0 0 24 24"><path d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/></symbol>
        <symbol id="i-copy" viewBox="0 0 24 24"><path d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="m4.5 12.75 6 6 9-13.5"/></symbol>
        <symbol id="i-thumb" viewBox="0 0 24 24"><path d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z"/></symbol>
        <symbol id="i-refresh" viewBox="0 0 24 24"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></symbol>
        <symbol id="i-sun" viewBox="0 0 24 24"><path d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></symbol>
        <symbol id="i-moon" viewBox="0 0 24 24"><path d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></symbol>
        <symbol id="i-more" viewBox="0 0 24 24"><path d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></symbol>
        <symbol id="i-pencil" viewBox="0 0 24 24"><path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></symbol>
        <symbol id="i-trash" viewBox="0 0 24 24"><path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></symbol>
        <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 18 18 6M6 6l12 12"/></symbol>
        <symbol id="i-down" viewBox="0 0 24 24"><path d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3"/></symbol>
        <symbol id="i-cube" viewBox="0 0 24 24"><path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></symbol>
        <symbol id="i-star" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.563.563 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></symbol>
        <symbol id="i-photo" viewBox="0 0 24 24"><path d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.41a2.25 2.25 0 0 1 3.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></symbol>
    </svg>

    @verbatim
    <style>
        .dz {
            --bg: #FFFFFF;
            --side: #F3F2EE;
            --card: #F6F5F2;
            --ink: #14161C;
            --muted: #666B76;
            --faint: #9A9EA8;
            --accent: #E9A216;
            --accent-soft: #FCF1D6;
            --hover: rgba(20, 22, 28, .05);
            --active: rgba(20, 22, 28, .08);
            --user: #F0EEE9;
            --ring: rgba(20, 22, 28, .07);
            --shadow: 0 1px 2px rgba(20, 22, 28, .05), 0 18px 40px -20px rgba(20, 22, 28, .28);
            --danger: #B42318;
            font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            position: relative;
            display: flex;
            height: calc(100vh - 160px);
            min-height: 560px;
            border-radius: 20px;
            overflow: hidden;
            background: var(--bg);
            box-shadow: var(--shadow);
        }
        .dz[data-theme="dark"] {
            --bg: #101217;
            --side: #151821;
            --card: #1A1D27;
            --ink: #ECEDEF;
            --muted: #9AA0AC;
            --faint: #6C7280;
            --accent: #F2B134;
            --accent-soft: rgba(242, 177, 52, .12);
            --hover: rgba(255, 255, 255, .05);
            --active: rgba(255, 255, 255, .09);
            --user: #212534;
            --ring: rgba(255, 255, 255, .08);
            --shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 18px 40px -20px rgba(0, 0, 0, .7);
            --danger: #F97066;
        }
        .dz *, .dz *::before, .dz *::after { box-sizing: border-box; }
        .dz button { font: inherit; color: inherit; background: none; border: 0; cursor: pointer; }
        .dz :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 8px; }
        .dz-i { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.6; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
        .dz-i.sm { width: 15px; height: 15px; }
        .dz-i.flip { transform: rotate(180deg); }
        .dz-scroll::-webkit-scrollbar, .dz-hist::-webkit-scrollbar { width: 6px; }
        .dz-scroll::-webkit-scrollbar-thumb, .dz-hist::-webkit-scrollbar-thumb { background: var(--active); border-radius: 999px; }

        /* ---------- Sidebar ---------- */
        .dz-side { width: 288px; flex-shrink: 0; display: flex; flex-direction: column; background: var(--side); transition: margin-left .25s ease, transform .25s ease; z-index: 30; }
        .dz.is-collapsed .dz-side { margin-left: -288px; }
        .dz-brand { display: flex; align-items: center; gap: 10px; padding: 16px 16px 8px 18px; }
        .dz-mark { width: 32px; height: 32px; border-radius: 10px; background: var(--ink); color: var(--accent); display: grid; place-items: center; }
        .dz-brand-name { font-weight: 700; font-size: 15px; letter-spacing: -.01em; }
        .dz-brand-sub { font-size: 11.5px; color: var(--muted); line-height: 1.2; }
        .dz-icon-btn { width: 34px; height: 34px; border-radius: 10px; display: inline-grid; place-items: center; color: var(--muted); transition: background .15s, color .15s; }
        .dz-icon-btn:hover { background: var(--hover); color: var(--ink); }
        .dz-newchat { margin: 8px 14px 6px; display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 12px; background: var(--ink); color: var(--bg); font-weight: 600; font-size: 14px; transition: transform .12s, opacity .15s; }
        .dz[data-theme="dark"] .dz-newchat { background: var(--accent); color: #14161C; }
        .dz-newchat:hover { opacity: .92; }
        .dz-newchat:active { transform: scale(.985); }
        .dz-newchat .dz-i { color: var(--accent); }
        .dz[data-theme="dark"] .dz-newchat .dz-i { color: #14161C; }
        .dz-kbd { margin-left: auto; font-size: 11px; font-weight: 600; opacity: .55; }
        .dz-search { position: relative; margin: 8px 14px 6px; }
        .dz-search .dz-i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--faint); width: 16px; height: 16px; }
        .dz-search input { width: 100%; background: var(--hover); border: 0; border-radius: 10px; padding: 9px 12px 9px 34px; font: inherit; font-size: 13.5px; color: var(--ink); outline: none; }
        .dz-search input::placeholder { color: var(--faint); }
        .dz-search input:focus { box-shadow: 0 0 0 2px var(--accent); }
        .dz-hist { flex: 1; overflow-y: auto; padding: 6px 10px 10px; }
        .dz-group-label { padding: 14px 10px 6px; font-size: 12px; font-weight: 600; color: var(--faint); }
        .dz-item { position: relative; display: flex; align-items: center; border-radius: 10px; transition: background .15s; }
        .dz-item:hover { background: var(--hover); }
        .dz-item.is-active { background: var(--active); }
        .dz-item.is-active::before { content: ""; position: absolute; left: 0; top: 9px; bottom: 9px; width: 3px; border-radius: 3px; background: var(--accent); }
        .dz-item-link { flex: 1; min-width: 0; padding: 9px 12px; font-size: 13.5px; color: var(--muted); text-decoration: none; display: block; }
        .dz-item.is-active .dz-item-link, .dz-item:hover .dz-item-link { color: var(--ink); }
        .dz-item.is-active .dz-item-link { font-weight: 600; }
        .dz-item-title { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dz-item-more { width: 28px; height: 28px; margin-right: 4px; border-radius: 8px; display: none; place-items: center; color: var(--muted); }
        .dz-item:hover .dz-item-more, .dz-item.menu-open .dz-item-more { display: grid; }
        .dz-item-more:hover { background: var(--active); color: var(--ink); }
        .dz-rename { width: 100%; margin: 4px 8px; padding: 6px 8px; font: inherit; font-size: 13.5px; color: var(--ink); background: var(--bg); border: 0; border-radius: 8px; box-shadow: 0 0 0 2px var(--accent); outline: none; }
        .dz-pop { position: absolute; right: 6px; top: calc(100% - 2px); z-index: 40; min-width: 150px; padding: 5px; background: var(--bg); border-radius: 12px; box-shadow: var(--shadow), 0 0 0 1px var(--ring); }
        .dz-pop button { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border-radius: 8px; font-size: 13.5px; text-align: left; }
        .dz-pop button:hover { background: var(--hover); }
        .dz-pop .danger { color: var(--danger); }
        .dz-nohist { padding: 36px 14px; text-align: center; color: var(--faint); font-size: 13px; line-height: 1.5; }
        .dz-nohist strong { display: block; color: var(--muted); font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .dz-user { display: flex; align-items: center; gap: 10px; padding: 12px 14px 14px 18px; }
        .dz-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); color: #14161C; font-size: 12px; font-weight: 700; display: grid; place-items: center; flex-shrink: 0; }
        .dz-user-meta { min-width: 0; flex: 1; }
        .dz-user-name { font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dz-user-role { font-size: 12px; color: var(--muted); }
        .dz-backdrop { display: none; }

        /* ---------- Main ---------- */
        .dz-main { position: relative; flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--bg); }
        .dz-head { display: flex; align-items: center; gap: 8px; padding: 12px 18px; }
        .dz-head-menu { display: none; }
        .dz.is-collapsed .dz-head-menu { display: inline-grid; }
        .dz-title { flex: 1; min-width: 0; font-size: 14.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dz-live { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border-radius: 999px; background: var(--hover); color: var(--muted); font-size: 12.5px; font-weight: 500; white-space: nowrap; }
        .dz-dot { position: relative; width: 8px; height: 8px; border-radius: 50%; background: #22A06B; }
        .dz-dot::after { content: ""; position: absolute; inset: 0; border-radius: 50%; background: #22A06B; animation: dz-ping 2s ease-out infinite; }
        @keyframes dz-ping { 0% { transform: scale(1); opacity: .6; } 80%, 100% { transform: scale(2.6); opacity: 0; } }

        .dz-scroll { flex: 1; overflow-y: auto; padding: 8px 24px 28px; scroll-behavior: smooth; }
        .dz-thread { max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 28px; }

        /* Empty state */
        .dz-empty { max-width: 760px; margin: 0 auto; min-height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 16px 0 24px; }
        .dz-hello { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 14.5px; font-weight: 500; }
        .dz-hello .dz-mark { width: 28px; height: 28px; border-radius: 9px; }
        .dz-h1 { font-family: 'Fraunces', ui-serif, Georgia, serif; font-weight: 500; font-size: clamp(32px, 5.2vw, 52px); line-height: 1.08; letter-spacing: -.025em; margin: 18px 0 0; max-width: 640px; }
        .dz-lede { margin: 14px 0 0; max-width: 520px; color: var(--muted); font-size: 15px; line-height: 1.65; }
        .dz-cards { margin-top: 34px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .dz-card { display: flex; flex-direction: column; gap: 14px; text-align: left; padding: 16px 18px 18px; border-radius: 16px; background: var(--card); transition: transform .18s ease, box-shadow .18s ease, background .18s; }
        .dz-card:hover { background: var(--bg); box-shadow: var(--shadow), 0 0 0 1px var(--accent); transform: translateY(-2px); }
        .dz-card-ic { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; background: var(--accent-soft); color: #B87408; }
        .dz[data-theme="dark"] .dz-card-ic { color: var(--accent); }
        .dz-card-t { font-size: 14.5px; font-weight: 600; }
        .dz-card-d { margin-top: 3px; font-size: 13px; line-height: 1.5; color: var(--muted); }

        /* Messages */
        .dz-msg { display: flex; gap: 14px; align-items: flex-start; }
        .dz-msg.user { justify-content: flex-end; }
        .dz-av { width: 32px; height: 32px; border-radius: 10px; background: var(--ink); color: var(--accent); display: grid; place-items: center; flex-shrink: 0; margin-top: 2px; }
        .dz[data-theme="dark"] .dz-av { background: var(--accent); color: #14161C; }
        .dz-ubub { max-width: 80%; padding: 11px 16px; border-radius: 18px 18px 6px 18px; background: var(--user); font-size: 14.5px; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
        .dz-ubub img { display: block; max-width: 240px; max-height: 200px; border-radius: 12px; margin-bottom: 8px; }
        .dz-col { min-width: 0; flex: 1; }
        .dz-body { font-size: 14.5px; line-height: 1.7; word-break: break-word; }
        .dz-body > :first-child { margin-top: 0; }
        .dz-body > :last-child { margin-bottom: 0; }
        .dz-body p { margin: 0 0 12px; }
        .dz-body h4 { margin: 18px 0 8px; font-size: 15px; font-weight: 700; }
        .dz-body ul, .dz-body ol { margin: 0 0 12px; padding-left: 22px; }
        .dz-body li { margin: 4px 0; padding-left: 2px; }
        .dz-body li::marker { color: var(--accent); font-weight: 700; }
        .dz-body strong { font-weight: 700; }
        .dz-body code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12.5px; padding: 2px 6px; border-radius: 6px; background: var(--hover); }
        .dz-body pre { margin: 0 0 12px; padding: 14px 16px; border-radius: 12px; background: var(--card); overflow-x: auto; }
        .dz-body pre code { padding: 0; background: none; }
        .dz-tbl { margin: 0 0 14px; overflow-x: auto; border-radius: 12px; background: var(--card); }
        .dz-tbl table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .dz-tbl th { text-align: left; padding: 10px 14px; font-weight: 600; color: var(--muted); font-size: 12.5px; white-space: nowrap; }
        .dz-tbl td { padding: 10px 14px; }
        .dz-tbl tbody tr:nth-child(odd) td { background: var(--hover); }
        .dz-acts { display: flex; gap: 2px; margin-top: 8px; opacity: 0; transition: opacity .15s; }
        .dz-msg:hover .dz-acts, .dz-msg:focus-within .dz-acts, .dz-msg.is-last .dz-acts { opacity: 1; }
        @media (hover: none) { .dz-acts { opacity: 1; } }
        .dz-act { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; color: var(--faint); transition: background .15s, color .15s; }
        .dz-act:hover { background: var(--hover); color: var(--ink); }
        .dz-act.on { color: var(--accent); }
        .dz-time { align-self: center; margin-left: 8px; font-size: 12px; color: var(--faint); }
        .dz-typing { display: flex; align-items: center; gap: 10px; padding: 8px 0; color: var(--muted); font-size: 13.5px; }
        .dz-dots { display: inline-flex; gap: 4px; }
        .dz-dots i { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); animation: dz-bounce 1.2s infinite ease-in-out; }
        .dz-dots i:nth-child(2) { animation-delay: .15s; }
        .dz-dots i:nth-child(3) { animation-delay: .3s; }
        @keyframes dz-bounce { 0%, 80%, 100% { transform: translateY(0); opacity: .4; } 40% { transform: translateY(-5px); opacity: 1; } }
        .dz-error { padding: 12px 14px; border-radius: 12px; background: rgba(180, 35, 24, .08); color: var(--danger); font-size: 14px; line-height: 1.5; }
        .dz-error button { margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: var(--bg); color: var(--ink); font-size: 13px; font-weight: 600; box-shadow: 0 0 0 1px var(--ring); }
        .dz-follow { display: flex; flex-wrap: wrap; gap: 8px; padding-left: 46px; margin-top: -12px; }
        .dz-follow button { padding: 8px 14px; border-radius: 999px; background: var(--hover); font-size: 13px; color: var(--ink); transition: background .15s; }
        .dz-follow button:hover { background: var(--accent-soft); }

        /* Composer */
        .dz-foot { padding: 0 24px 14px; position: relative; }
        .dz-wrap { max-width: 760px; margin: 0 auto; }
        .dz-composer { background: var(--bg); border-radius: 22px; box-shadow: var(--shadow), 0 0 0 1px var(--ring); transition: box-shadow .2s; padding: 10px 10px 10px 14px; }
        .dz-composer:focus-within { box-shadow: var(--shadow), 0 0 0 2px var(--accent); }
        .dz-composer.drag { box-shadow: var(--shadow), 0 0 0 2px var(--accent); background: var(--accent-soft); }
        .dz-chips { display: flex; gap: 8px; flex-wrap: wrap; padding: 2px 0 8px; }
        .dz-chip { display: inline-flex; align-items: center; gap: 10px; padding: 5px 6px 5px 5px; border-radius: 12px; background: var(--card); font-size: 12.5px; max-width: 240px; }
        .dz-chip img { width: 34px; height: 34px; border-radius: 8px; object-fit: cover; }
        .dz-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dz-chip button { width: 24px; height: 24px; border-radius: 7px; display: grid; place-items: center; color: var(--muted); }
        .dz-chip button:hover { background: var(--active); }
        #assistant-input { width: 100%; resize: none; border: 0; outline: none; background: transparent; font: inherit; font-size: 15px; line-height: 1.55; color: var(--ink); padding: 6px 4px; max-height: 160px; display: block; }
        #assistant-input::placeholder { color: var(--faint); }
        .dz-tools { display: flex; align-items: center; gap: 4px; margin-top: 4px; }
        .dz-count { margin-left: auto; margin-right: 8px; font-size: 12px; color: var(--faint); font-variant-numeric: tabular-nums; }
        .dz-count.warn { color: var(--danger); }
        #assistant-send-btn { width: 38px; height: 38px; border-radius: 50%; background: var(--ink); color: var(--accent); display: grid; place-items: center; transition: transform .12s, opacity .15s; }
        .dz[data-theme="dark"] #assistant-send-btn { background: var(--accent); color: #14161C; }
        #assistant-send-btn:hover:not(:disabled) { transform: scale(1.06); }
        #assistant-send-btn:active:not(:disabled) { transform: scale(.94); }
        #assistant-send-btn:disabled { opacity: .3; cursor: not-allowed; }
        .dz-hint { max-width: 760px; margin: 10px auto 0; text-align: center; font-size: 12px; color: var(--faint); }
        .dz-hint kbd { font: inherit; font-weight: 600; color: var(--muted); }
        .dz-jump { position: absolute; left: 50%; top: -48px; transform: translateX(-50%); width: 36px; height: 36px; border-radius: 50%; background: var(--bg); color: var(--ink); display: none; place-items: center; box-shadow: var(--shadow), 0 0 0 1px var(--ring); }
        .dz-jump.show { display: grid; }
        .dz-toast { position: absolute; left: 50%; bottom: 96px; transform: translate(-50%, 12px); padding: 10px 16px; border-radius: 12px; background: var(--ink); color: var(--bg); font-size: 13px; font-weight: 500; opacity: 0; pointer-events: none; transition: opacity .2s, transform .2s; z-index: 60; }
        .dz[data-theme="dark"] .dz-toast { background: var(--accent); color: #14161C; }
        .dz-toast.show { opacity: 1; transform: translate(-50%, 0); }

        /* ---------- Mobile ---------- */
        @media (max-width: 768px) {
            .dz { border-radius: 14px; height: calc(100dvh - 120px); }
            .dz-side { position: absolute; inset: 0 auto 0 0; transform: translateX(-100%); margin-left: 0 !important; box-shadow: var(--shadow); }
            .dz.is-open .dz-side { transform: translateX(0); }
            .dz.is-open .dz-backdrop { display: block; position: absolute; inset: 0; background: rgba(10, 12, 16, .45); z-index: 20; }
            .dz-head-menu { display: inline-grid !important; }
            .dz-cards { grid-template-columns: 1fr; }
            .dz-scroll, .dz-foot { padding-left: 16px; padding-right: 16px; }
            .dz-live, .dz-hint .dz-kbds { display: none; }
            .dz-ubub { max-width: 88%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .dz *, .dz *::before, .dz *::after { transition: none !important; animation: none !important; scroll-behavior: auto !important; }
        }
    </style>
    @endverbatim

    <div class="dz" id="dz-root" data-theme="light">

        {{-- ============ Sidebar ============ --}}
        <aside class="dz-side" id="dz-side" aria-label="Chat history">
            <div class="dz-brand">
                <div class="dz-mark"><svg class="dz-i"><use href="#i-sparkles"/></svg></div>
                <div style="flex:1;min-width:0">
                    <div class="dz-brand-name">DZAI</div>
                    <div class="dz-brand-sub">Intelligence Studio</div>
                </div>
                <button type="button" class="dz-icon-btn" id="dz-collapse" title="Close sidebar" aria-label="Close sidebar">
                    <svg class="dz-i"><use href="#i-menu"/></svg>
                </button>
            </div>

            <button id="new-chat-btn" type="button" class="dz-newchat">
                <svg class="dz-i"><use href="#i-plus"/></svg>
                New chat
            </button>

            <label class="dz-search">
                <svg class="dz-i"><use href="#i-search"/></svg>
                <input type="search" id="dz-search" placeholder="Search chats" aria-label="Search chats" autocomplete="off">
            </label>

            <div class="dz-hist" id="dz-hist">
                @if(count($conversations) == 0)
                    <div class="dz-nohist" id="dz-nohist">
                        <strong>No chats yet</strong>
                        Ask DZAI a question and it will be saved here.
                    </div>
                @endif

                @foreach($groups as $label => $items)
                    @if(count($items))
                        <div class="dz-group" data-group="{{ $label }}">
                            <div class="dz-group-label">{{ $label }}</div>
                            @foreach($items as $conv)
                                @php $isActive = $activeConversationId == $conv->id; @endphp
                                <div class="dz-item {{ $isActive ? 'is-active' : '' }}"
                                     data-id="{{ $conv->id }}"
                                     data-title="{{ $conv->title }}"
                                     @if($canRename) data-rename-url="{{ route('admin.assistant.rename', $conv->id) }}" @endif
                                     @if($canDelete) data-delete-url="{{ route('admin.assistant.destroy', $conv->id) }}" @endif>
                                    <a class="dz-item-link" href="?conversation_id={{ $conv->id }}" @if($isActive) aria-current="page" @endif>
                                        <span class="dz-item-title">{{ $conv->title }}</span>
                                    </a>
                                    @if($canRename || $canDelete)
                                        <button type="button" class="dz-item-more" data-act="more" aria-label="Chat options" aria-haspopup="menu">
                                            <svg class="dz-i sm"><use href="#i-more"/></svg>
                                        </button>
                                        <div class="dz-pop" role="menu" hidden>
                                            @if($canRename)
                                                <button type="button" role="menuitem" data-act="rename"><svg class="dz-i sm"><use href="#i-pencil"/></svg>Rename</button>
                                            @endif
                                            @if($canDelete)
                                                <button type="button" role="menuitem" class="danger" data-act="delete"><svg class="dz-i sm"><use href="#i-trash"/></svg>Delete</button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                <div class="dz-nohist" id="dz-nomatch" hidden>
                    <strong>No matches</strong>
                    Try a different word.
                </div>
            </div>

            <div class="dz-user">
                <div class="dz-avatar">{{ $initials }}</div>
                <div class="dz-user-meta">
                    <div class="dz-user-name">{{ $userName }}</div>
                    <div class="dz-user-role">Admin</div>
                </div>
                <button type="button" class="dz-icon-btn" id="dz-theme" title="Switch theme" aria-label="Switch theme">
                    <svg class="dz-i" id="dz-theme-ic"><use href="#i-moon"/></svg>
                </button>
            </div>
        </aside>
        <div class="dz-backdrop" id="dz-backdrop"></div>

        {{-- ============ Main ============ --}}
        <main class="dz-main">
            <header class="dz-head">
                <button type="button" class="dz-icon-btn dz-head-menu" id="dz-open" title="Open sidebar" aria-label="Open sidebar">
                    <svg class="dz-i"><use href="#i-menu"/></svg>
                </button>
                <h2 class="dz-title" id="dz-title">{{ $activeTitle }}</h2>
                <span class="dz-live"><span class="dz-dot"></span>Using live shop data</span>
                <button type="button" class="dz-icon-btn" id="dz-copy-all" title="Copy conversation" aria-label="Copy conversation">
                    <svg class="dz-i"><use href="#i-copy"/></svg>
                </button>
            </header>

            {{-- Messages --}}
            <div id="assistant-messages" class="dz-scroll">
                @if(count($messages) == 0)
                    <section class="dz-empty" id="dz-empty">
                        <div class="dz-hello">
                            <div class="dz-mark"><svg class="dz-i sm"><use href="#i-sparkles"/></svg></div>
                            <span id="dz-greet">Hello, {{ $firstName }}</span>
                        </div>
                        <h1 class="dz-h1">What should we look into today?</h1>
                        <p class="dz-lede">Ask about stock, sales, or your menu. DZAI checks your shop's live data and explains what it finds in plain language.</p>

                        <div class="dz-cards">
                            <button type="button" class="dz-card suggested-chip" data-prompt="Which ingredients are low on stock or close to expiring?">
                                <span class="dz-card-ic"><svg class="dz-i"><use href="#i-cube"/></svg></span>
                                <span>
                                    <span class="dz-card-t" style="display:block">Check stock</span>
                                    <span class="dz-card-d" style="display:block">Find low or expiring ingredients and what to reorder.</span>
                                </span>
                            </button>
                            <button type="button" class="dz-card suggested-chip" data-prompt="How are today's earnings and order volume doing?">
                                <span class="dz-card-ic"><svg class="dz-i"><use href="#i-chart"/></svg></span>
                                <span>
                                    <span class="dz-card-t" style="display:block">Review sales</span>
                                    <span class="dz-card-d" style="display:block">See today's earnings, order count, and busy hours.</span>
                                </span>
                            </button>
                            <button type="button" class="dz-card suggested-chip" data-prompt="Which menu items sell best, and which ones are underperforming?">
                                <span class="dz-card-ic"><svg class="dz-i"><use href="#i-star"/></svg></span>
                                <span>
                                    <span class="dz-card-t" style="display:block">Rank menu items</span>
                                    <span class="dz-card-d" style="display:block">Spot your best sellers and the dishes that lag behind.</span>
                                </span>
                            </button>
                            <button type="button" class="dz-card suggested-chip" data-action="upload">
                                <span class="dz-card-ic"><svg class="dz-i"><use href="#i-photo"/></svg></span>
                                <span>
                                    <span class="dz-card-t" style="display:block">Analyze a photo</span>
                                    <span class="dz-card-d" style="display:block">Upload a picture of your shelves or a receipt.</span>
                                </span>
                            </button>
                        </div>
                    </section>
                @endif

                <div class="dz-thread" id="dz-thread" role="log" aria-live="polite" aria-label="Conversation">
                    @foreach($messages as $msg)
                        @php
                            $time = isset($msg->created_at) ? Carbon::parse($msg->created_at)->format('g:i A') : null;
                        @endphp
                        @if($msg->role === 'user')
                            <div class="dz-msg user" data-role="user" data-raw="{{ $msg->content }}">
                                <div class="dz-ubub">{{ $msg->content }}</div>
                            </div>
                        @else
                            <div class="dz-msg bot" data-role="assistant" data-raw="{{ $msg->content }}" data-server="1">
                                <div class="dz-av"><svg class="dz-i sm"><use href="#i-sparkles"/></svg></div>
                                <div class="dz-col">
                                    <div class="dz-body">{!! nl2br(e($msg->content)) !!}</div>
                                    <div class="dz-acts"></div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Composer --}}
            <div class="dz-foot">
                <button type="button" class="dz-jump" id="dz-jump" aria-label="Jump to latest message">
                    <svg class="dz-i"><use href="#i-down"/></svg>
                </button>

                <div id="assistant-config" data-config="{{ json_encode($config) }}" hidden></div>

                <form id="assistant-form" class="dz-wrap" autocomplete="off">
                    <div class="dz-composer" id="dz-composer">
                        <div class="dz-chips" id="dz-chips" hidden></div>
                        <textarea id="assistant-input" rows="1" maxlength="500" placeholder="Ask about stock, sales, or the menu" aria-label="Message DZAI"></textarea>
                        <div class="dz-tools">
                            <button type="button" id="assistant-upload-btn" class="dz-icon-btn" title="Attach an image" aria-label="Attach an image">
                                <svg class="dz-i"><use href="#i-clip"/></svg>
                            </button>
                            <input type="file" id="assistant-file-input" accept="image/*" hidden>
                            <span class="dz-count" id="dz-count"></span>
                            <button type="submit" id="assistant-send-btn" aria-label="Send message" disabled>
                                <svg class="dz-i" id="dz-send-ic"><use href="#i-up"/></svg>
                            </button>
                        </div>
                    </div>
                </form>
                <p class="dz-hint"><span class="dz-kbds"><kbd>Enter</kbd> to send, <kbd>Shift + Enter</kbd> for a new line. </span>DZAI can make mistakes, so check important figures.</p>
                <div class="dz-toast" id="dz-toast" role="status"></div>
            </div>
        </main>
    </div>

    @verbatim
    <script>
    (function () {
        'use strict';

        /* =========================================================
           ADAPTER: the only place that depends on your Laravel controller.
           Change field names / response keys here if they differ.
           ========================================================= */
        function buildPayload(text, file, conversationId) {
            var fd = new FormData();
            fd.append('message', text);
            fd.append('question', text); // alias, in case the controller expects "question"
            if (conversationId) fd.append('conversation_id', conversationId);
            if (file) fd.append('image', file);
            return fd;
        }
        function parseReply(d) {
            if (!d) return '';
            var x = d.data || {};
            return d.reply || d.response || d.answer || d.message || d.content ||
                   x.reply || x.response || x.answer || x.message || '';
        }
        function parseMeta(d) {
            var x = (d && d.data) || {};
            return {
                id: (d && (d.conversation_id || d.conversationId)) || x.conversation_id || null,
                title: (d && d.title) || x.title || null,
                suggestions: (d && d.suggestions) || x.suggestions || []
            };
        }
        /* ========================================================= */

        var root = document.getElementById('dz-root');
        var cfg = {};
        try { cfg = JSON.parse(document.getElementById('assistant-config').getAttribute('data-config') || '{}'); } catch (e) {}

        var $ = function (s, r) { return (r || document).querySelector(s); };
        var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

        var scroller = $('#assistant-messages'), thread = $('#dz-thread'), form = $('#assistant-form');
        var input = $('#assistant-input'), sendBtn = $('#assistant-send-btn'), sendIc = $('#dz-send-ic');
        var fileInput = $('#assistant-file-input'), chipsEl = $('#dz-chips'), countEl = $('#dz-count');
        var composer = $('#dz-composer'), jump = $('#dz-jump'), toastEl = $('#dz-toast');
        var hist = $('#dz-hist'), titleEl = $('#dz-title');

        var state = { conversationId: cfg.conversationId || null, busy: false, file: null, fileUrl: null,
                      controller: null, last: null, revealTimer: null, finishReveal: null };

        /* ---------- helpers ---------- */
        function ic(name, cls) { return '<svg class="dz-i ' + (cls || '') + '"><use href="#i-' + name + '"/></svg>'; }
        function el(tag, cls, html) { var n = document.createElement(tag); if (cls) n.className = cls; if (html != null) n.innerHTML = html; return n; }
        function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
        function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) {} return null; }
        function isMobile() { return window.matchMedia('(max-width: 768px)').matches; }

        var toastT;
        function toast(msg) {
            toastEl.textContent = msg; toastEl.classList.add('show');
            clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('show'); }, 2200);
        }
        function copyText(t) {
            if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(t);
            return new Promise(function (res) {
                var ta = el('textarea'); ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta); res();
            });
        }

        /* ---------- tiny, safe markdown ---------- */
        function inline(s) {
            return s.split(/(`[^`]+`)/).map(function (p) {
                if (p.length > 1 && p.charAt(0) === '`' && p.charAt(p.length - 1) === '`') return '<code>' + esc(p.slice(1, -1)) + '</code>';
                return esc(p).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                             .replace(/(^|[^*])\*([^*\s][^*]*?)\*(?!\*)/g, '$1<em>$2</em>');
            }).join('');
        }
        function md(src) {
            var lines = String(src).replace(/\r/g, '').split('\n'), out = [], i = 0;
            var ulRe = /^\s*[-*\u2022]\s+/, olRe = /^\s*\d+[.)]\s+/;
            function isSep(s) { return /^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/.test(s); }
            function cells(s) { s = s.trim(); if (s.charAt(0) === '|') s = s.slice(1); if (s.charAt(s.length - 1) === '|') s = s.slice(0, -1); return s.split('|').map(function (c) { return c.trim(); }); }
            while (i < lines.length) {
                var l = lines[i];
                if (/^```/.test(l)) {
                    var code = []; i++;
                    while (i < lines.length && !/^```/.test(lines[i])) { code.push(lines[i]); i++; }
                    i++; out.push('<pre><code>' + esc(code.join('\n')) + '</code></pre>'); continue;
                }
                if (l.indexOf('|') > -1 && i + 1 < lines.length && isSep(lines[i + 1])) {
                    var head = cells(l), rows = []; i += 2;
                    while (i < lines.length && lines[i].trim() && lines[i].indexOf('|') > -1) { rows.push(cells(lines[i])); i++; }
                    out.push('<div class="dz-tbl"><table><thead><tr>' + head.map(function (h) { return '<th>' + inline(h) + '</th>'; }).join('') +
                        '</tr></thead><tbody>' + rows.map(function (r) { return '<tr>' + r.map(function (c) { return '<td>' + inline(c) + '</td>'; }).join('') + '</tr>'; }).join('') +
                        '</tbody></table></div>');
                    continue;
                }
                var h = l.match(/^#{1,4}\s+(.*)$/);
                if (h) { out.push('<h4>' + inline(h[1]) + '</h4>'); i++; continue; }
                if (ulRe.test(l)) {
                    var u = []; while (i < lines.length && ulRe.test(lines[i])) { u.push('<li>' + inline(lines[i].replace(ulRe, '')) + '</li>'); i++; }
                    out.push('<ul>' + u.join('') + '</ul>'); continue;
                }
                if (olRe.test(l)) {
                    var o = []; while (i < lines.length && olRe.test(lines[i])) { o.push('<li>' + inline(lines[i].replace(olRe, '')) + '</li>'); i++; }
                    out.push('<ol>' + o.join('') + '</ol>'); continue;
                }
                if (!l.trim()) { i++; continue; }
                var p = [];
                while (i < lines.length && lines[i].trim() && !/^```|^#{1,4}\s/.test(lines[i]) && !ulRe.test(lines[i]) && !olRe.test(lines[i])) { p.push(inline(lines[i])); i++; }
                if (!p.length) { p.push(inline(l)); i++; }
                out.push('<p>' + p.join('<br>') + '</p>');
            }
            return out.join('');
        }

        /* ---------- scrolling ---------- */
        function nearBottom() { return scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight < 140; }
        function toBottom(smooth) {
            scroller.style.scrollBehavior = smooth ? 'smooth' : 'auto';
            scroller.scrollTop = scroller.scrollHeight;
        }
        scroller.addEventListener('scroll', function () {
            jump.classList.toggle('show', scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight > 260);
        });
        jump.addEventListener('click', function () { toBottom(true); });

        /* ---------- message builders ---------- */
        function hideEmpty() { var e = $('#dz-empty'); if (e) e.parentNode.removeChild(e); }
        function clearFollow() { $$('.dz-follow', thread).forEach(function (n) { n.parentNode.removeChild(n); }); }
        function markLast() {
            $$('.dz-msg.is-last', thread).forEach(function (n) { n.classList.remove('is-last'); });
            var bots = $$('.dz-msg.bot', thread);
            if (bots.length) bots[bots.length - 1].classList.add('is-last');
        }
        function actsHtml() {
            return '<button type="button" class="dz-act" data-act="copy" title="Copy" aria-label="Copy reply">' + ic('copy', 'sm') + '</button>' +
                   '<button type="button" class="dz-act" data-act="up" title="Good answer" aria-label="Good answer">' + ic('thumb', 'sm') + '</button>' +
                   '<button type="button" class="dz-act" data-act="down" title="Not helpful" aria-label="Not helpful">' + ic('thumb', 'sm flip') + '</button>' +
                   '<button type="button" class="dz-act" data-act="regen" title="Regenerate" aria-label="Regenerate reply">' + ic('refresh', 'sm') + '</button>';
        }
        function addUser(text, imgUrl) {
            var row = el('div', 'dz-msg user'); row.dataset.role = 'user'; row.dataset.raw = text;
            var b = el('div', 'dz-ubub');
            if (imgUrl) { var im = el('img'); im.src = imgUrl; im.alt = 'Attached image'; b.appendChild(im); }
            b.appendChild(document.createTextNode(text));
            row.appendChild(b); thread.appendChild(row); toBottom(true);
        }
        function addTyping() {
            var row = el('div', 'dz-msg bot dz-typing-row');
            row.appendChild(el('div', 'dz-av', ic('sparkles', 'sm')));
            var col = el('div', 'dz-col', '<div class="dz-typing"><span class="dz-dots"><i></i><i></i><i></i></span><span>Analyzing your data</span></div>');
            row.appendChild(col); thread.appendChild(row); toBottom(true); return row;
        }
        function addBot(text, animate) {
            var row = el('div', 'dz-msg bot'); row.dataset.role = 'assistant'; row.dataset.raw = text;
            row.appendChild(el('div', 'dz-av', ic('sparkles', 'sm')));
            var col = el('div', 'dz-col'), body = el('div', 'dz-body'), acts = el('div', 'dz-acts', actsHtml());
            col.appendChild(body); col.appendChild(acts); row.appendChild(col); thread.appendChild(row);
            markLast();
            if (animate) reveal(body, text); else body.innerHTML = md(text);
            return row;
        }
        function addError(msg, retry) {
            var row = el('div', 'dz-msg bot'); row.dataset.role = 'error';
            row.appendChild(el('div', 'dz-av', ic('sparkles', 'sm')));
            var col = el('div', 'dz-col'), box = el('div', 'dz-error');
            box.appendChild(document.createTextNode(msg + ' '));
            var b = el('button', '', ic('refresh', 'sm') + 'Try again'); b.type = 'button';
            b.addEventListener('click', function () { row.parentNode.removeChild(row); retry(); });
            box.appendChild(el('br')); box.appendChild(b); col.appendChild(box); row.appendChild(col);
            thread.appendChild(row); toBottom(true);
        }
        function addFollow(list) {
            if (!list || !list.length) return;
            var wrap = el('div', 'dz-follow');
            list.slice(0, 3).forEach(function (s) {
                var b = el('button'); b.type = 'button'; b.textContent = s;
                b.addEventListener('click', function () { send(s); }); wrap.appendChild(b);
            });
            thread.appendChild(wrap); toBottom(true);
        }
        function reveal(body, text) {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var words = text.split(/(\s+)/);
            if (reduce || words.length < 8) { body.innerHTML = md(text); toBottom(true); return; }
            var i = 0, step = Math.max(2, Math.ceil(words.length / 70));
            function finish() { clearInterval(state.revealTimer); state.revealTimer = null; state.finishReveal = null; body.innerHTML = md(text); }
            state.finishReveal = finish;
            state.revealTimer = setInterval(function () {
                i += step; body.innerHTML = md(words.slice(0, i).join(''));
                if (nearBottom()) toBottom(false);
                if (i >= words.length) { finish(); setBusy(false); }
            }, 22);
        }

        /* ---------- composer ---------- */
        function autosize() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 160) + 'px'; }
        function refreshSend() { sendBtn.disabled = !state.busy && !input.value.trim() && !state.file; }
        function updateCount() {
            var n = input.value.length;
            countEl.textContent = n > 380 ? n + '/500' : '';
            countEl.classList.toggle('warn', n >= 480);
        }
        function setBusy(b) {
            state.busy = b;
            sendIc.innerHTML = '<use href="#i-' + (b ? 'stop' : 'up') + '"/>';
            sendBtn.setAttribute('aria-label', b ? 'Stop generating' : 'Send message');
            refreshSend();
        }
        function setFile(f) {
            if (state.fileUrl) { URL.revokeObjectURL(state.fileUrl); state.fileUrl = null; }
            state.file = null; chipsEl.innerHTML = ''; chipsEl.hidden = true;
            if (f) {
                if (!/^image\//.test(f.type)) { toast('Only images can be attached.'); refreshSend(); return; }
                if (f.size > 8 * 1024 * 1024) { toast('That image is over 8 MB.'); refreshSend(); return; }
                state.file = f; state.fileUrl = URL.createObjectURL(f);
                var chip = el('div', 'dz-chip'), im = el('img'); im.src = state.fileUrl; im.alt = '';
                var nm = el('span'); nm.textContent = f.name;
                var rm = el('button', '', ic('x', 'sm')); rm.type = 'button'; rm.setAttribute('aria-label', 'Remove image');
                rm.addEventListener('click', function () { setFile(null); });
                chip.appendChild(im); chip.appendChild(nm); chip.appendChild(rm);
                chipsEl.appendChild(chip); chipsEl.hidden = false; input.focus();
            }
            refreshSend();
        }

        input.addEventListener('input', function () { autosize(); updateCount(); refreshSend(); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); if (!sendBtn.disabled) form.requestSubmit(); }
        });
        $('#assistant-upload-btn').addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', function () { if (fileInput.files[0]) setFile(fileInput.files[0]); fileInput.value = ''; });
        input.addEventListener('paste', function (e) {
            var items = (e.clipboardData || {}).items || [];
            for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image/') === 0) { setFile(items[i].getAsFile()); e.preventDefault(); break; }
            }
        });
        ['dragenter', 'dragover'].forEach(function (ev) {
            root.addEventListener(ev, function (e) { if (e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types, 'Files') > -1) { e.preventDefault(); composer.classList.add('drag'); } });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            root.addEventListener(ev, function (e) {
                composer.classList.remove('drag');
                if (ev === 'drop' && e.dataTransfer && e.dataTransfer.files.length) { e.preventDefault(); setFile(e.dataTransfer.files[0]); }
            });
        });

        /* ---------- sending ---------- */
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (state.busy) { stop(); return; }
            send(input.value);
        });
        function stop() {
            if (state.finishReveal) { state.finishReveal(); setBusy(false); return; }
            if (state.controller) state.controller.abort();
        }
        function send(text) {
            text = (text || '').trim();
            if (state.busy || (!text && !state.file)) return;
            var file = state.file, url = state.fileUrl;
            if (!text) text = 'What can you tell me about this image?';
            hideEmpty(); clearFollow();
            addUser(text, url);
            // keep object URL alive for the bubble; just detach from composer
            state.file = null; state.fileUrl = null; chipsEl.innerHTML = ''; chipsEl.hidden = true;
            input.value = ''; autosize(); updateCount();
            state.last = { text: text, file: file };
            request(text, file);
        }
        function request(text, file) {
            setBusy(true);
            var typing = addTyping();
            state.controller = new AbortController();
            fetch(cfg.endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: buildPayload(text, file, state.conversationId),
                signal: state.controller.signal,
                credentials: 'same-origin'
            }).then(function (res) {
                var ct = res.headers.get('content-type') || '';
                var parse = ct.indexOf('json') > -1 ? res.json() : res.text().then(function (t) { return { reply: t }; });
                return parse.then(function (d) {
                    if (!res.ok) throw new Error((d && (d.message || d.error)) || 'The request failed (' + res.status + ').');
                    return d;
                });
            }).then(function (d) {
                if (typing.parentNode) typing.parentNode.removeChild(typing);
                var reply = parseReply(d);
                if (!reply) throw new Error('DZAI returned an empty answer.');
                var meta = parseMeta(d);
                addBot(reply, true);
                afterReply(text, meta);
                if (!state.revealTimer) setBusy(false);
            }).catch(function (err) {
                if (typing.parentNode) typing.parentNode.removeChild(typing);
                setBusy(false);
                if (err && err.name === 'AbortError') { toast('Stopped.'); return; }
                addError((err && err.message) || 'Something went wrong.', function () { request(text, file); });
            });
        }
        function afterReply(text, meta) {
            if (meta.id && !state.conversationId) {
                state.conversationId = meta.id;
                try { history.replaceState(null, '', '?conversation_id=' + encodeURIComponent(meta.id)); } catch (e) {}
                addHistoryItem(meta.id, meta.title || text.slice(0, 48));
                titleEl.textContent = meta.title || text.slice(0, 48);
            } else if (!state.conversationId && titleEl.textContent === 'New chat') {
                titleEl.textContent = text.slice(0, 48);
            }
            addFollow(meta.suggestions);
        }

        /* ---------- message actions (delegated) ---------- */
        thread.addEventListener('click', function (e) {
            var b = e.target.closest('[data-act]'); if (!b) return;
            var row = b.closest('.dz-msg'), act = b.getAttribute('data-act');
            if (act === 'copy') { copyText(row.dataset.raw || '').then(function () { toast('Copied'); }); }
            if (act === 'up' || act === 'down') {
                var other = row.querySelector('[data-act="' + (act === 'up' ? 'down' : 'up') + '"]');
                if (other) other.classList.remove('on'); b.classList.toggle('on');
            }
            if (act === 'regen' && !state.busy) {
                var users = $$('.dz-msg.user', thread), lastU = users[users.length - 1];
                if (!lastU) return;
                row.parentNode.removeChild(row); clearFollow();
                var txt = lastU.dataset.raw; state.last = { text: txt, file: null }; request(txt, null);
            }
        });

        /* ---------- hydrate server-rendered messages ---------- */
        $$('.dz-msg.bot[data-server]', thread).forEach(function (row) {
            row.querySelector('.dz-body').innerHTML = md(row.dataset.raw || '');
            row.querySelector('.dz-acts').innerHTML = actsHtml();
        });
        markLast();
        toBottom(false);

        /* ---------- sidebar ---------- */
        function newChat() { window.location.href = window.location.pathname; }
        $('#new-chat-btn').addEventListener('click', newChat);

        function setSidebar(open) {
            if (isMobile()) { root.classList.toggle('is-open', open); }
            else { root.classList.toggle('is-collapsed', !open); store('dz-side', open ? 'open' : 'closed'); }
        }
        $('#dz-collapse').addEventListener('click', function () { setSidebar(false); });
        $('#dz-open').addEventListener('click', function () { setSidebar(true); });
        $('#dz-backdrop').addEventListener('click', function () { setSidebar(false); });
        if (!isMobile() && store('dz-side') === 'closed') root.classList.add('is-collapsed');

        // search
        var searchEl = $('#dz-search');
        searchEl.addEventListener('input', function () {
            var q = searchEl.value.trim().toLowerCase(), any = false;
            $$('.dz-item', hist).forEach(function (it) {
                var hit = !q || (it.dataset.title || '').toLowerCase().indexOf(q) > -1;
                it.style.display = hit ? '' : 'none'; if (hit) any = true;
            });
            $$('.dz-group', hist).forEach(function (g) {
                g.style.display = $$('.dz-item', g).some(function (i) { return i.style.display !== 'none'; }) ? '' : 'none';
            });
            var nm = $('#dz-nomatch'); nm.hidden = any || !$$('.dz-item', hist).length;
        });

        // add a new chat to the list without reloading
        function addHistoryItem(id, title) {
            var nh = $('#dz-nohist'); if (nh) nh.parentNode.removeChild(nh);
            var g = $('.dz-group[data-group="Today"]', hist);
            if (!g) {
                g = el('div', 'dz-group'); g.dataset.group = 'Today';
                g.appendChild(el('div', 'dz-group-label', 'Today'));
                hist.insertBefore(g, hist.firstChild);
            }
            $$('.dz-item.is-active', hist).forEach(function (n) { n.classList.remove('is-active'); });
            var item = el('div', 'dz-item is-active'); item.dataset.id = id; item.dataset.title = title;
            var a = el('a', 'dz-item-link'); a.href = '?conversation_id=' + encodeURIComponent(id);
            var s = el('span', 'dz-item-title'); s.textContent = title; a.appendChild(s); item.appendChild(a);
            g.insertBefore(item, g.children[1] || null);
        }

        // item menu, rename, delete
        function closeMenus() {
            $$('.dz-pop', hist).forEach(function (p) { p.hidden = true; });
            $$('.dz-item.menu-open', hist).forEach(function (i) { i.classList.remove('menu-open'); });
        }
        var jsonHeaders = { 'X-CSRF-TOKEN': cfg.csrf || '', 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        hist.addEventListener('click', function (e) {
            var b = e.target.closest('[data-act]'); if (!b) return;
            var item = b.closest('.dz-item'), act = b.getAttribute('data-act');
            if (act === 'more') {
                var pop = item.querySelector('.dz-pop'), wasHidden = pop.hidden;
                closeMenus(); pop.hidden = !wasHidden; item.classList.toggle('menu-open', wasHidden); e.stopPropagation();
            }
            if (act === 'rename') { closeMenus(); startRename(item); }
            if (act === 'delete') {
                closeMenus();
                if (!window.confirm('Delete this chat? This cannot be undone.')) return;
                fetch(item.dataset.deleteUrl, { method: 'DELETE', headers: jsonHeaders, credentials: 'same-origin' })
                    .then(function (r) {
                        if (!r.ok) throw new Error();
                        if (String(item.dataset.id) === String(state.conversationId)) { newChat(); return; }
                        var g = item.parentNode; g.removeChild(item);
                        if (!$$('.dz-item', g).length) g.parentNode.removeChild(g);
                        toast('Chat deleted');
                    }).catch(function () { toast('Could not delete the chat.'); });
            }
        });
        document.addEventListener('click', function (e) { if (!e.target.closest('.dz-pop')) closeMenus(); });
        function startRename(item) {
            var link = item.querySelector('.dz-item-link'), old = item.dataset.title || '';
            var inp = el('input', 'dz-rename'); inp.value = old; inp.maxLength = 80; inp.setAttribute('aria-label', 'Chat name');
            link.style.display = 'none'; item.insertBefore(inp, link); inp.focus(); inp.select();
            var done = false;
            function finish(save) {
                if (done) return; done = true;
                var v = inp.value.trim();
                inp.parentNode.removeChild(inp); link.style.display = '';
                if (!save || !v || v === old) return;
                fetch(item.dataset.renameUrl, { method: 'PATCH', headers: jsonHeaders, body: JSON.stringify({ title: v }), credentials: 'same-origin' })
                    .then(function (r) {
                        if (!r.ok) throw new Error();
                        item.dataset.title = v; link.querySelector('.dz-item-title').textContent = v;
                        if (String(item.dataset.id) === String(state.conversationId)) titleEl.textContent = v;
                    }).catch(function () { toast('Could not rename the chat.'); });
            }
            inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') finish(true); if (e.key === 'Escape') finish(false); });
            inp.addEventListener('blur', function () { finish(true); });
        }

        /* ---------- header + cards ---------- */
        $('#dz-copy-all').addEventListener('click', function () {
            var rows = $$('.dz-msg[data-role]', thread).filter(function (r) { return r.dataset.role !== 'error'; });
            if (!rows.length) { toast('Nothing to copy yet.'); return; }
            var t = rows.map(function (r) { return (r.dataset.role === 'user' ? 'You' : 'DZAI') + ':\n' + (r.dataset.raw || ''); }).join('\n\n');
            copyText(t).then(function () { toast('Conversation copied'); });
        });
        scroller.addEventListener('click', function (e) {
            var c = e.target.closest('.dz-card'); if (!c) return;
            if (c.getAttribute('data-action') === 'upload') { fileInput.click(); return; }
            send(c.getAttribute('data-prompt'));
        });

        /* ---------- theme ---------- */
        function applyTheme(t) {
            root.setAttribute('data-theme', t);
            $('#dz-theme-ic').innerHTML = '<use href="#i-' + (t === 'dark' ? 'sun' : 'moon') + '"/>';
        }
        var savedTheme = store('dz-theme');
        applyTheme(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
        $('#dz-theme').addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next); store('dz-theme', next);
        });

        /* ---------- greeting ---------- */
        var g = $('#dz-greet');
        if (g) {
            var h = new Date().getHours(), part = h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
            g.textContent = part + ', ' + g.textContent.replace('Hello, ', '');
        }

        /* ---------- shortcuts ---------- */
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault(); if (root.classList.contains('is-collapsed') || (isMobile() && !root.classList.contains('is-open'))) setSidebar(true);
                searchEl.focus(); searchEl.select();
            }
            if (e.key === '/' && !/^(INPUT|TEXTAREA)$/.test((document.activeElement || {}).tagName)) { e.preventDefault(); input.focus(); }
            if (e.key === 'Escape') { closeMenus(); if (isMobile()) root.classList.remove('is-open'); }
        });

        refreshSend();
    })();
    </script>
    @endverbatim
@endsection