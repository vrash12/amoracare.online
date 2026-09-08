@extends('layouts.dashboard', ['title' => 'AI Legal Guidance'])
{{-- laravel-app/resources/views/parent/ai/index.blade.php --}}

@section('content')
    <style>
        :root {
            --ai-primary: #8b2f18;
            --ai-primary-dark: #6f2413;
            --ai-primary-soft: #fff4ef;
            --ai-accent: #2563eb;
            --ai-success: #15803d;
            --ai-warning: #b45309;
            --ai-danger: #b42318;
            --ai-text: #172033;
            --ai-muted: #667085;
            --ai-border: #e4e7ec;
            --ai-surface: #ffffff;
            --ai-surface-soft: #f8fafc;
            --ai-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .ai-page {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            gap: 18px;
            color: var(--ai-text);
        }

        .ai-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 22px;
            padding: 26px;
            border: 1px solid #f1d6cc;
            border-radius: 24px;
            background:
                radial-gradient(circle at top right, rgba(139, 47, 24, 0.12), transparent 34%),
                linear-gradient(135deg, #ffffff 0%, #fff8f5 58%, #f8fafc 100%);
            box-shadow: var(--ai-shadow);
        }

        .ai-hero::after {
            content: "";
            position: absolute;
            right: -62px;
            bottom: -78px;
            width: 210px;
            height: 210px;
            border-radius: 999px;
            border: 34px solid rgba(139, 47, 24, 0.05);
            pointer-events: none;
        }

        .ai-hero-copy {
            position: relative;
            z-index: 1;
            max-width: 780px;
        }

        .ai-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            padding: 7px 11px;
            border: 1px solid #f1d6cc;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.86);
            color: var(--ai-primary);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .ai-hero h1 {
            margin: 0;
            font-size: clamp(26px, 3vw, 38px);
            line-height: 1.12;
            letter-spacing: -0.03em;
            color: #101828;
        }

        .ai-hero p {
            max-width: 720px;
            margin: 12px 0 0;
            color: var(--ai-muted);
            line-height: 1.7;
            font-size: 15px;
        }

        .ai-service-status {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-width: max-content;
            padding: 10px 13px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #166534;
            font-size: 13px;
            font-weight: 800;
        }

        .ai-service-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.13);
        }

        .ai-workspace {
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .ai-sidebar {
            display: grid;
            gap: 14px;
            position: sticky;
            top: 18px;
        }

        .ai-side-card,
        .ai-chat-card {
            border: 1px solid var(--ai-border);
            background: var(--ai-surface);
            box-shadow: var(--ai-shadow);
        }

        .ai-side-card {
            border-radius: 20px;
            padding: 18px;
        }

        .ai-side-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 6px;
            color: #101828;
            font-size: 15px;
            font-weight: 900;
        }

        .ai-side-title i {
            color: var(--ai-primary);
        }

        .ai-side-description {
            margin: 0 0 14px;
            color: var(--ai-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .ai-topic-list {
            display: grid;
            gap: 8px;
        }

        .ai-question-chip {
            --chat-button-color: #8b2f18;
            --chat-button-soft: #fff4ef;
            --chat-button-border: #f1d6cc;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border: 1px solid var(--ai-border);
            border-radius: 13px;
            background: #ffffff;
            color: #344054;
            text-align: left;
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease, transform 0.18s ease;
        }

        .ai-question-chip i {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 10px;
            background: var(--chat-button-soft);
            color: var(--chat-button-color);
        }

        .ai-question-chip:hover {
            transform: translateY(-1px);
            border-color: var(--chat-button-border);
            background: var(--chat-button-soft);
        }

        .ai-question-copy {
            min-width: 0;
            display: grid;
            gap: 2px;
        }

        .ai-question-copy strong {
            color: #344054;
            font-size: 13px;
        }

        .ai-question-copy small {
            color: var(--ai-muted);
            font-size: 11px;
            font-weight: 600;
            line-height: 1.35;
        }

        .ai-question-chip::after {
            content: "\F138";
            margin-left: auto;
            color: var(--chat-button-color);
            font-family: "bootstrap-icons";
            font-size: 13px;
        }

        .ai-question-chip.is-documents {
            --chat-button-color: #1d4ed8;
            --chat-button-soft: #eff6ff;
            --chat-button-border: #93c5fd;
        }

        .ai-question-chip.is-status {
            --chat-button-color: #047857;
            --chat-button-soft: #ecfdf5;
            --chat-button-border: #6ee7b7;
        }

        .ai-question-chip.is-requirements {
            --chat-button-color: #7c3aed;
            --chat-button-soft: #f5f3ff;
            --chat-button-border: #c4b5fd;
        }

        .ai-question-chip.is-home-study {
            --chat-button-color: #b45309;
            --chat-button-soft: #fffbeb;
            --chat-button-border: #fcd34d;
        }

        .ai-question-chip.is-forum {
            --chat-button-color: #be185d;
            --chat-button-soft: #fdf2f8;
            --chat-button-border: #f9a8d4;
        }

        .ai-question-chip.is-privacy {
            --chat-button-color: #475569;
            --chat-button-soft: #f8fafc;
            --chat-button-border: #cbd5e1;
        }

        .ai-question-chip.is-timeline {
            --chat-button-color: #0369a1;
            --chat-button-soft: #f0f9ff;
            --chat-button-border: #7dd3fc;
        }

        .ai-question-chip.is-process {
            --chat-button-color: #0f766e;
            --chat-button-soft: #f0fdfa;
            --chat-button-border: #5eead4;
        }

        .ai-question-chip.is-support {
            --chat-button-color: #9a3412;
            --chat-button-soft: #fff7ed;
            --chat-button-border: #fdba74;
        }

        .ai-question-chip:focus-visible,
        .ai-action-btn:focus-visible,
        .ai-followup-chip:focus-visible,
        .ai-send-btn:focus-visible,
        .ai-clear-btn:focus-visible,
        .ai-scroll-latest:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.22);
            outline-offset: 2px;
        }

        .ai-question-chip:disabled,
        .ai-action-btn:disabled,
        .ai-followup-chip:disabled,
        .ai-send-btn:disabled,
        .ai-clear-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
        }

        .ai-capability-list {
            display: grid;
            gap: 11px;
            margin-top: 12px;
        }

        .ai-capability {
            display: grid;
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 9px;
            align-items: start;
        }

        .ai-capability-icon {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: #f0fdf4;
            color: var(--ai-success);
            font-size: 13px;
        }

        .ai-capability strong {
            display: block;
            color: #344054;
            font-size: 13px;
        }

        .ai-capability span {
            display: block;
            margin-top: 2px;
            color: var(--ai-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .ai-privacy-card {
            border-color: #fed7aa;
            background: #fffaf5;
        }

        .ai-privacy-card .ai-side-title,
        .ai-privacy-card .ai-side-title i {
            color: #9a3412;
        }

        .ai-privacy-card p {
            margin: 0;
            color: #9a3412;
            font-size: 12px;
            line-height: 1.6;
        }

        .ai-chat-card {
            min-width: 0;
            overflow: hidden;
            border-radius: 24px;
        }

        .ai-chat-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 18px 20px;
            border-bottom: 1px solid var(--ai-border);
            background: linear-gradient(180deg, #ffffff, #fbfcfd);
        }

        .ai-chat-identity {
            display: flex;
            gap: 12px;
            align-items: center;
            min-width: 0;
        }

        .ai-guide-avatar {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 15px;
            background: linear-gradient(145deg, var(--ai-primary), #b85b3e);
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 9px 22px rgba(139, 47, 24, 0.25);
        }

        .ai-chat-identity h2 {
            margin: 0;
            color: #101828;
            font-size: 17px;
            font-weight: 900;
        }

        .ai-chat-identity p {
            margin: 3px 0 0;
            color: var(--ai-muted);
            font-size: 12px;
        }

        .ai-chat-tools {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-message-count {
            color: var(--ai-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .ai-clear-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 38px;
            padding: 0 12px;
            border: 1px solid var(--ai-border);
            border-radius: 11px;
            background: #ffffff;
            color: #475467;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
        }

        .ai-clear-btn:hover {
            border-color: #f4b8b2;
            background: #fff5f4;
            color: var(--ai-danger);
        }

        .ai-chat-stage {
            position: relative;
            background:
                linear-gradient(rgba(248, 250, 252, 0.9), rgba(248, 250, 252, 0.9)),
                radial-gradient(circle at 15% 10%, rgba(139, 47, 24, 0.08), transparent 28%);
        }

        .ai-chat-box {
            height: min(58vh, 620px);
            min-height: 480px;
            overflow-y: auto;
            overscroll-behavior: contain;
            scroll-behavior: smooth;
            padding: 24px 22px 30px;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .ai-chat-box::-webkit-scrollbar {
            width: 9px;
        }

        .ai-chat-box::-webkit-scrollbar-thumb {
            border: 2px solid transparent;
            border-radius: 999px;
            background: #cbd5e1;
            background-clip: padding-box;
        }

        .ai-message-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            margin-bottom: 18px;
        }

        .ai-message-row.user {
            justify-content: flex-end;
        }

        .ai-message-avatar {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border: 1px solid var(--ai-border);
            border-radius: 11px;
            background: #ffffff;
            color: var(--ai-primary);
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 5px 14px rgba(15, 23, 42, 0.06);
        }

        .ai-message-row.user .ai-message-avatar {
            order: 2;
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .ai-message {
            width: fit-content;
            max-width: min(80%, 720px);
            padding: 14px 15px;
            border: 1px solid var(--ai-border);
            border-radius: 18px 18px 18px 6px;
            background: #ffffff;
            color: #27364b;
            line-height: 1.62;
            overflow-wrap: anywhere;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .ai-message.user {
            border-color: #bfdbfe;
            border-radius: 18px 18px 6px 18px;
            background: linear-gradient(145deg, #eff6ff, #dbeafe);
            color: #1e3a5f;
        }

        .ai-message.assistant.typing {
            border-color: #f1d6cc;
            background: #fffaf8;
        }

        .ai-role-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 7px;
        }

        .ai-role {
            color: var(--ai-primary);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .ai-message.user .ai-role {
            color: #1d4ed8;
        }

        .ai-message-time {
            color: #98a2b3;
            font-size: 10px;
            font-weight: 700;
        }

        .ai-message-content p {
            margin: 0 0 10px;
        }

        .ai-message-content p:last-child {
            margin-bottom: 0;
        }

        .ai-message-content h3,
        .ai-message-content h4 {
            margin: 12px 0 7px;
            color: #101828;
            line-height: 1.35;
        }

        .ai-message-content h3 {
            font-size: 16px;
        }

        .ai-message-content h4 {
            font-size: 14px;
        }

        .ai-message-content ul,
        .ai-message-content ol {
            margin: 8px 0 10px 19px;
            padding: 0;
        }

        .ai-message-content li {
            margin-bottom: 6px;
            padding-left: 2px;
        }

        .ai-message-content strong {
            color: #172033;
        }

        .ai-source-section {
            display: grid;
            gap: 9px;
            margin-top: 13px;
            padding-top: 12px;
            border-top: 1px solid #eef1f5;
        }

        .ai-source-heading {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #344054;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .ai-source-heading.online {
            color: #1d4ed8;
        }

        .ai-message-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .ai-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            max-width: 100%;
            min-height: 34px;
            padding: 7px 10px;
            border: 1px solid #dce3ec;
            border-radius: 11px;
            background: #f8fafc;
            color: #475467;
            font-size: 11px;
            font-weight: 800;
            line-height: 1.35;
            text-decoration: none;
            overflow-wrap: anywhere;
            transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease, transform 0.18s ease;
        }

        .ai-meta-pill.online {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .ai-meta-pill.online:hover {
            transform: translateY(-1px);
            border-color: #60a5fa;
            background: #dbeafe;
            color: #1e40af;
        }

        .ai-meta-pill.local:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .ai-source-label {
            display: grid;
            gap: 2px;
            min-width: 0;
        }

        .ai-source-title {
            white-space: normal;
        }

        .ai-source-domain {
            color: #667085;
            font-size: 10px;
            font-weight: 700;
        }

        .ai-disclaimer {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-top: 11px;
            padding: 10px 11px;
            border: 1px solid #fed7aa;
            border-radius: 11px;
            background: #fff7ed;
            color: #9a3412;
            font-size: 11px;
            line-height: 1.5;
        }

        .ai-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 11px;
        }

        .ai-action-btn,
        .ai-followup-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dce3ec;
            background: #ffffff;
            color: #475467;
            font: inherit;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .ai-action-btn {
            min-height: 32px;
            padding: 0 9px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
        }

        .ai-action-btn:hover,
        .ai-followup-chip:hover {
            border-color: #e7b9a8;
            background: #fff8f5;
            color: var(--ai-primary);
        }

        .ai-followups {
            margin-top: 12px;
        }

        .ai-followups-title {
            margin-bottom: 7px;
            color: var(--ai-muted);
            font-size: 11px;
            font-weight: 800;
        }

        .ai-followup-list {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .ai-followup-chip {
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-align: left;
        }

        .ai-typing {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--ai-muted);
        }

        .ai-dots {
            display: inline-flex;
            gap: 4px;
        }

        .ai-dots span {
            width: 6px;
            height: 6px;
            display: inline-block;
            border-radius: 999px;
            background: var(--ai-primary);
            animation: aiBounce 1.2s infinite ease-in-out;
        }

        .ai-dots span:nth-child(2) {
            animation-delay: 0.15s;
        }

        .ai-dots span:nth-child(3) {
            animation-delay: 0.3s;
        }

        .ai-cursor {
            display: inline-block;
            width: 6px;
            height: 16px;
            margin-left: 3px;
            border-radius: 999px;
            background: var(--ai-primary);
            vertical-align: -3px;
            animation: aiBlink 0.9s steps(2, start) infinite;
        }

        @keyframes aiBounce {
            0%, 80%, 100% {
                opacity: 0.3;
                transform: translateY(0);
            }
            40% {
                opacity: 1;
                transform: translateY(-3px);
            }
        }

        @keyframes aiBlink {
            0%, 45% { opacity: 1; }
            46%, 100% { opacity: 0; }
        }

        .ai-empty {
            max-width: 560px;
            margin: 58px auto;
            padding: 26px;
            border: 1px dashed #cbd5e1;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.78);
            text-align: center;
            color: var(--ai-muted);
        }

        .ai-empty-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin: 0 auto 12px;
            border-radius: 18px;
            background: var(--ai-primary-soft);
            color: var(--ai-primary);
            font-size: 22px;
        }

        .ai-empty h3 {
            margin: 0 0 7px;
            color: #101828;
            font-size: 17px;
        }

        .ai-empty p {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
        }

        .ai-scroll-latest {
            position: absolute;
            right: 18px;
            bottom: 16px;
            display: none;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 0 11px;
            border: 1px solid #d0d5dd;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.96);
            color: #344054;
            font: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12);
        }

        .ai-scroll-latest.visible {
            display: inline-flex;
        }

        .ai-composer {
            padding: 15px 16px 16px;
            border-top: 1px solid var(--ai-border);
            background: #ffffff;
        }

        .ai-composer-shell {
            border: 1px solid #d0d5dd;
            border-radius: 17px;
            background: #ffffff;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .ai-composer-shell:focus-within {
            border-color: #d89b86;
            box-shadow: 0 0 0 4px rgba(139, 47, 24, 0.09);
        }

        .ai-input-area textarea {
            width: 100%;
            min-height: 76px;
            max-height: 190px;
            display: block;
            resize: none;
            overflow-y: auto;
            padding: 14px 15px 8px;
            border: 0;
            outline: 0;
            background: transparent;
            color: #101828;
            font: inherit;
            font-size: 14px;
            line-height: 1.55;
        }

        .ai-input-area textarea::placeholder {
            color: #98a2b3;
        }

        .ai-composer-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 7px 8px 8px 13px;
        }

        .ai-input-hints {
            display: flex;
            gap: 10px;
            align-items: center;
            min-width: 0;
            color: var(--ai-muted);
            font-size: 11px;
        }

        .ai-character-count {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .ai-character-count.near-limit {
            color: var(--ai-warning);
            font-weight: 800;
        }

        .ai-character-count.over-limit {
            color: var(--ai-danger);
            font-weight: 900;
        }

        .ai-send-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border: 0;
            border-radius: 13px;
            background: linear-gradient(145deg, var(--ai-primary), #a94327);
            color: #ffffff;
            font: inherit;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 9px 20px rgba(139, 47, 24, 0.2);
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .ai-send-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(139, 47, 24, 0.26);
        }

        .ai-status {
            min-height: 20px;
            margin-top: 8px;
            padding: 0 3px;
            color: var(--ai-muted);
            font-size: 12px;
        }

        .ai-status.error {
            color: var(--ai-danger);
            font-weight: 700;
        }

        .ai-status.success {
            color: var(--ai-success);
            font-weight: 700;
        }

        .ai-legal-note {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            padding: 14px 16px;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 12px;
            line-height: 1.55;
        }

        .ai-legal-note i {
            margin-top: 1px;
            font-size: 17px;
        }

        @media (max-width: 1024px) {
            .ai-workspace {
                grid-template-columns: 1fr;
            }

            .ai-sidebar {
                position: static;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ai-sidebar .ai-side-card:first-child {
                grid-column: 1 / -1;
            }

            .ai-topic-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .ai-page {
                gap: 13px;
            }

            .ai-hero {
                padding: 20px;
                border-radius: 20px;
                flex-direction: column;
            }

            .ai-service-status {
                min-width: 0;
            }

            .ai-sidebar {
                grid-template-columns: 1fr;
            }

            .ai-sidebar .ai-side-card:first-child {
                grid-column: auto;
            }

            .ai-topic-list {
                grid-template-columns: 1fr;
            }

            .ai-chat-card {
                border-radius: 19px;
            }

            .ai-chat-header {
                align-items: flex-start;
                padding: 15px;
            }

            .ai-message-count {
                display: none;
            }

            .ai-clear-btn span {
                display: none;
            }

            .ai-clear-btn {
                width: 40px;
                padding: 0;
                justify-content: center;
            }

            .ai-chat-box {
                height: 56vh;
                min-height: 430px;
                padding: 18px 12px 26px;
            }

            .ai-message {
                max-width: calc(100% - 46px);
                padding: 12px 13px;
            }

            .ai-message-avatar {
                width: 31px;
                height: 31px;
                border-radius: 10px;
            }

            .ai-composer {
                padding: 11px;
            }

            .ai-composer-footer {
                align-items: flex-end;
            }

            .ai-input-hints .ai-shortcut-hint {
                display: none;
            }

            .ai-send-btn span {
                display: none;
            }

            .ai-send-btn {
                width: 42px;
                padding: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    <div class="ai-page">
        <section class="ai-hero" aria-labelledby="aiPageTitle">
            <div class="ai-hero-copy">
                <div class="ai-eyebrow">
                    <i class="bi bi-stars" aria-hidden="true"></i>
                    Parent guidance assistant
                </div>

                <h1 id="aiPageTitle">Understand your adoption journey with clearer guidance.</h1>

                <p>
                    Ask about adoption requirements, your parent-visible application progress,
                    document checklists, and general NACC, RACCO, or DSWD procedures.
                </p>
            </div>

            <div class="ai-service-status" aria-label="AI guidance service is available">
                <span class="ai-service-dot" aria-hidden="true"></span>
                Guidance service available
            </div>
        </section>

        <div class="ai-workspace">
            <aside class="ai-sidebar" aria-label="AI guidance help and suggested questions">
                <section class="ai-side-card">
                    <h2 class="ai-side-title">
                        <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                        Initial chat requests
                    </h2>
                    <p class="ai-side-description">
                        Choose a starter based on {{ $hasAdoptionCase ? 'your current application' : 'where you are in the adoption process' }}.
                    </p>

                    <div class="ai-topic-list">
                        @foreach($initialChatRequests as $initialRequest)
                            <button
                                type="button"
                                class="ai-question-chip {{ $initialRequest['class'] }}"
                                data-question="{{ $initialRequest['question'] }}"
                                data-send-immediately="true"
                            >
                                <i class="bi {{ $initialRequest['icon'] }}" aria-hidden="true"></i>
                                <span class="ai-question-copy">
                                    <strong>{{ $initialRequest['title'] }}</strong>
                                    <small>{{ $initialRequest['description'] }}</small>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="ai-side-card">
                    <h2 class="ai-side-title">
                        <i class="bi bi-patch-check" aria-hidden="true"></i>
                        What I can help with
                    </h2>

                    <div class="ai-capability-list">
                        <div class="ai-capability">
                            <div class="ai-capability-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
                            <div>
                                <strong>Application progress</strong>
                                <span>Explain your current parent-visible case status and next steps.</span>
                            </div>
                        </div>

                        <div class="ai-capability">
                            <div class="ai-capability-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
                            <div>
                                <strong>Document guidance</strong>
                                <span>Identify pending, rejected, expired, or verified parent requirements.</span>
                            </div>
                        </div>

                        <div class="ai-capability">
                            <div class="ai-capability-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
                            <div>
                                <strong>General procedures</strong>
                                <span>Explain adoption terms and official processes in plain language.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ai-side-card ai-privacy-card">
                    <h2 class="ai-side-title">
                        <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                        Privacy protected
                    </h2>
                    <p>
                        Child profiles, matching rankings, donor records, confidential notes,
                        and final placement decisions are restricted and cannot be shown here.
                    </p>
                </section>
            </aside>

            <main class="ai-chat-card" aria-label="Conversation with AmoraCare Guide">
                <header class="ai-chat-header">
                    <div class="ai-chat-identity">
                        <div class="ai-guide-avatar" aria-hidden="true">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <div>
                            <h2>AmoraCare Guide</h2>
                            <p>Informational adoption and application guidance</p>
                        </div>
                    </div>

                    <div class="ai-chat-tools">
                        <span class="ai-message-count" id="messageCount">0 messages</span>
                        <button
                            id="clearBtn"
                            type="button"
                            class="ai-clear-btn"
                            title="Clear this conversation"
                        >
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                            <span>Clear chat</span>
                        </button>
                    </div>
                </header>

                <div class="ai-chat-stage">
                    <div
                        id="chatBox"
                        class="ai-chat-box"
                        role="log"
                        aria-live="polite"
                        aria-relevant="additions text"
                        aria-label="AI guidance conversation"
                        tabindex="0"
                    ></div>

                    <button id="scrollLatestBtn" type="button" class="ai-scroll-latest">
                        <i class="bi bi-arrow-down" aria-hidden="true"></i>
                        Latest message
                    </button>
                </div>

                <div class="ai-composer">
                    <div class="ai-input-area">
                        <div class="ai-composer-shell">
                            <label for="userInput" class="visually-hidden">Ask an adoption-related question</label>
                            <textarea
                                id="userInput"
                                maxlength="2000"
                                rows="3"
                                placeholder="Ask about your documents, application status, requirements, or next steps..."
                                aria-describedby="composerHelp characterCount"
                            ></textarea>

                            <div class="ai-composer-footer">
                                <div class="ai-input-hints" id="composerHelp">
                                    <span class="ai-shortcut-hint">
                                        <i class="bi bi-keyboard" aria-hidden="true"></i>
                                        Enter to send, Shift + Enter for a new line
                                    </span>
                                    <span id="characterCount" class="ai-character-count">0 / 2000</span>
                                </div>

                                <button id="sendBtn" type="button" class="ai-send-btn">
                                    <span>Send message</span>
                                    <i class="bi bi-send-fill" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div id="status" class="ai-status" role="status" aria-live="polite"></div>
                    </div>
                </div>
            </main>
        </div>

        <div class="ai-legal-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>
                <strong>Guidance only:</strong>
                This assistant provides informational support and does not replace advice from DSWD,
                NACC, RACCO, licensed social workers, courts, or qualified legal professionals.
            </div>
        </div>
    </div>

    <script>
        const CHAT_URL = @json(route('parent.ai.chat'));
        const CLEAR_URL = @json(route('parent.ai.clear'));
        const CSRF_TOKEN = @json(csrf_token());
        const USER_NAME = @json(auth()->user()?->name ?? 'You');
        const SERVER_MESSAGES = @json($chatMessages ?? []);
        const LEGACY_BROWSER_STORAGE_KEYS = [
            "amoracare_parent_ai_chat_v4",
            "amoracare_parent_ai_chat_v3"
        ];
        const MAX_MESSAGE_LENGTH = 2000;
        const REQUEST_TIMEOUT_MS = 120000;
        const DEFAULT_GREETING = @json($defaultGreeting);

        const defaultMessage = {
            role: "assistant",
            content: DEFAULT_GREETING,
            sources: [],
            disclaimer: null,
            isTyping: false,
            createdAt: new Date().toISOString()
        };

        removeLegacyBrowserHistory();

        let isSending = false;
        let messages = loadMessages();

        const chatBox = document.getElementById("chatBox");
        const userInput = document.getElementById("userInput");
        const statusText = document.getElementById("status");
        const sendBtn = document.getElementById("sendBtn");
        const clearBtn = document.getElementById("clearBtn");
        const characterCount = document.getElementById("characterCount");
        const messageCount = document.getElementById("messageCount");
        const scrollLatestBtn = document.getElementById("scrollLatestBtn");
        const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        function removeLegacyBrowserHistory() {
            try {
                LEGACY_BROWSER_STORAGE_KEYS.forEach(key => localStorage.removeItem(key));
            } catch (error) {
                // Storage may be disabled. The page never reads browser-stored chat data.
            }
        }

        function loadMessages() {
            if (!Array.isArray(SERVER_MESSAGES) || SERVER_MESSAGES.length === 0) {
                return [{ ...defaultMessage }];
            }

            return SERVER_MESSAGES
                .filter(item => item && ["user", "assistant"].includes(item.role))
                .map(item => ({
                    role: item.role,
                    content: String(item.content || ""),
                    sources: Array.isArray(item.sources) ? item.sources : [],
                    disclaimer: item.disclaimer || null,
                    isTyping: false,
                    createdAt: item.createdAt || new Date().toISOString()
                }));
        }

        function escapeHtml(text) {
            return String(text || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatInlineMarkdown(text) {
            return escapeHtml(text)
                .replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>")
                .replace(/\*(.+?)\*/g, "<em>$1</em>");
        }

        function formatAssistantContent(text, isTyping = false) {
            if (!text && isTyping) {
                return `
                    <span class="ai-typing">
                        Preparing a helpful answer
                        <span class="ai-dots" aria-hidden="true">
                            <span></span><span></span><span></span>
                        </span>
                    </span>
                `;
            }

            if (!text) {
                return "<p>No response was received.</p>";
            }

            const lines = String(text)
                .replace(/\r\n/g, "\n")
                .replace(/\n{3,}/g, "\n\n")
                .trim()
                .split("\n");

            let html = "";
            let listType = null;

            const closeList = () => {
                if (!listType) return;
                html += listType === "ol" ? "</ol>" : "</ul>";
                listType = null;
            };

            lines.forEach(rawLine => {
                const line = rawLine.trim();

                if (!line) {
                    closeList();
                    return;
                }

                if (/^###\s+/.test(line)) {
                    closeList();
                    html += `<h4>${formatInlineMarkdown(line.replace(/^###\s+/, ""))}</h4>`;
                    return;
                }

                if (/^##\s+/.test(line)) {
                    closeList();
                    html += `<h3>${formatInlineMarkdown(line.replace(/^##\s+/, ""))}</h3>`;
                    return;
                }

                const bulletMatch = line.match(/^[-*+]\s+(.+)/);
                const numberMatch = line.match(/^\d+[.)]\s+(.+)/);

                if (bulletMatch || numberMatch) {
                    const nextType = numberMatch ? "ol" : "ul";

                    if (listType !== nextType) {
                        closeList();
                        html += nextType === "ol" ? "<ol>" : "<ul>";
                        listType = nextType;
                    }

                    html += `<li>${formatInlineMarkdown((bulletMatch || numberMatch)[1])}</li>`;
                    return;
                }

                closeList();
                html += `<p>${formatInlineMarkdown(line)}</p>`;
            });

            closeList();

            if (isTyping) {
                html += '<span class="ai-cursor" aria-hidden="true"></span>';
            }

            return html || `<p>${formatInlineMarkdown(text)}</p>`;
        }

        function formatUserContent(text) {
            return `<p>${escapeHtml(text || "")}</p>`;
        }

        function getInitials(name) {
            const parts = String(name || "You")
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            if (parts.length === 0) return "YO";
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
        }

        function formatTime(value) {
            const date = value ? new Date(value) : new Date();

            if (Number.isNaN(date.getTime())) {
                return "";
            }

            return new Intl.DateTimeFormat(undefined, {
                hour: "numeric",
                minute: "2-digit"
            }).format(date);
        }

        function getFollowUpSuggestions(text) {
            const normalized = String(text || "").toLowerCase();

            if (normalized.includes("document") || normalized.includes("requirement")) {
                return [
                    "Which items need action first?",
                    "What happens after I submit them?",
                    "Who verifies my documents?"
                ];
            }

            if (normalized.includes("status") || normalized.includes("application")) {
                return [
                    "What should I do next?",
                    "Do I have any pending documents?",
                    "Who can confirm this status?"
                ];
            }

            if (normalized.includes("home study")) {
                return [
                    "Who prepares the Home Study Report?",
                    "What information does it contain?",
                    "What happens after it is verified?"
                ];
            }

            if (normalized.includes("child") || normalized.includes("profile") || normalized.includes("matching")) {
                return [
                    "Why are child profiles restricted?",
                    "Who handles matching decisions?",
                    "What information can parents view?"
                ];
            }

            return [
                "What documents do I still need?",
                "What is my application status?",
                "What should I do next?"
            ];
        }

        function normalizeSourceUrl(value) {
            if (!value) return null;

            try {
                const parsed = new URL(String(value));

                if (!["http:", "https:"].includes(parsed.protocol)) {
                    return null;
                }

                return parsed;
            } catch (error) {
                return null;
            }
        }

        function createSourceElement(source) {
            const title = source?.title || source?.source || "Guidance source";
            const detail = source?.chunk_index !== undefined
                ? `Section ${source.chunk_index}`
                : "";
            const parsedUrl = normalizeSourceUrl(source?.url);
            const isOnline = Boolean(parsedUrl);
            const element = isOnline
                ? document.createElement("a")
                : document.createElement("span");

            element.className = `ai-meta-pill ${isOnline ? "online" : "local"}`;

            if (isOnline) {
                element.href = parsedUrl.href;
                element.target = "_blank";
                element.rel = "noopener noreferrer nofollow";
                element.referrerPolicy = "no-referrer";
                element.title = `Open ${parsedUrl.hostname} in a new tab`;
                element.setAttribute("aria-label", `Open source: ${title}`);
            }

            const icon = document.createElement("i");
            icon.className = isOnline ? "bi bi-box-arrow-up-right" : "bi bi-journal-text";
            icon.setAttribute("aria-hidden", "true");

            const label = document.createElement("span");
            label.className = "ai-source-label";

            const titleSpan = document.createElement("span");
            titleSpan.className = "ai-source-title";
            titleSpan.textContent = title;
            label.appendChild(titleSpan);

            const secondary = isOnline ? parsedUrl.hostname.replace(/^www\./, "") : detail;

            if (secondary) {
                const secondarySpan = document.createElement("span");
                secondarySpan.className = "ai-source-domain";
                secondarySpan.textContent = secondary;
                label.appendChild(secondarySpan);
            }

            element.appendChild(icon);
            element.appendChild(label);
            return element;
        }

        function createSourceGroup(title, sources, online = false) {
            const section = document.createElement("section");
            section.className = "ai-source-section";

            const heading = document.createElement("div");
            heading.className = `ai-source-heading${online ? " online" : ""}`;

            const headingIcon = document.createElement("i");
            headingIcon.className = online ? "bi bi-globe2" : "bi bi-database-check";
            headingIcon.setAttribute("aria-hidden", "true");

            const headingText = document.createElement("span");
            headingText.textContent = title;

            heading.appendChild(headingIcon);
            heading.appendChild(headingText);

            const list = document.createElement("div");
            list.className = "ai-message-meta";

            sources.forEach(source => {
                list.appendChild(createSourceElement(source));
            });

            section.appendChild(heading);
            section.appendChild(list);
            return section;
        }

        function renderChat({ forceBottom = true } = {}) {
            const wasNearBottom = isNearBottom();
            chatBox.innerHTML = "";

            if (!messages.length) {
                chatBox.innerHTML = `
                    <div class="ai-empty">
                        <div class="ai-empty-icon"><i class="bi bi-chat-square-heart"></i></div>
                        <h3>Start a new conversation</h3>
                        <p>Ask about your application, required documents, or a general adoption procedure.</p>
                    </div>
                `;
            }

            messages.forEach((msg, index) => {
                const row = document.createElement("div");
                row.className = `ai-message-row ${msg.role}`;
                row.dataset.messageIndex = String(index);

                const avatar = document.createElement("div");
                avatar.className = "ai-message-avatar";
                avatar.setAttribute("aria-hidden", "true");

                if (msg.role === "assistant") {
                    const icon = document.createElement("i");
                    icon.className = "bi bi-heart-pulse";
                    avatar.appendChild(icon);
                } else {
                    avatar.textContent = getInitials(USER_NAME);
                }

                const messageDiv = document.createElement("article");
                messageDiv.className = `ai-message ${msg.role}${msg.isTyping ? " typing" : ""}`;

                const roleRow = document.createElement("div");
                roleRow.className = "ai-role-row";

                const roleDiv = document.createElement("div");
                roleDiv.className = "ai-role";
                roleDiv.textContent = msg.role === "user" ? "You" : "AmoraCare Guide";

                const timeDiv = document.createElement("time");
                timeDiv.className = "ai-message-time";
                timeDiv.dateTime = msg.createdAt || "";
                timeDiv.textContent = formatTime(msg.createdAt);

                roleRow.appendChild(roleDiv);
                roleRow.appendChild(timeDiv);

                const contentDiv = document.createElement("div");
                contentDiv.className = "ai-message-content";
                contentDiv.innerHTML = msg.role === "assistant"
                    ? formatAssistantContent(msg.content, Boolean(msg.isTyping))
                    : formatUserContent(msg.content);

                messageDiv.appendChild(roleRow);
                messageDiv.appendChild(contentDiv);

                if (msg.role === "assistant" && !msg.isTyping) {
                    if (Array.isArray(msg.sources) && msg.sources.length > 0) {
                        const uniqueSources = [];
                        const seenSources = new Set();

                        msg.sources.forEach(source => {
                            const key = source?.url || `${source?.type || "source"}:${source?.title || ""}:${source?.chunk_index ?? ""}`;

                            if (!seenSources.has(key)) {
                                seenSources.add(key);
                                uniqueSources.push(source);
                            }
                        });

                        const onlineSources = uniqueSources
                            .filter(source => normalizeSourceUrl(source?.url))
                            .slice(0, 5);
                        const localSources = uniqueSources
                            .filter(source => !normalizeSourceUrl(source?.url))
                            .slice(0, 5);

                        if (onlineSources.length > 0) {
                            messageDiv.appendChild(
                                createSourceGroup(
                                    onlineSources.length === 1 ? "Online source" : "Online sources",
                                    onlineSources,
                                    true
                                )
                            );
                        }

                        if (localSources.length > 0) {
                            messageDiv.appendChild(
                                createSourceGroup(
                                    localSources.length === 1 ? "Supporting source" : "Supporting sources",
                                    localSources,
                                    false
                                )
                            );
                        }
                    }

                    if (msg.disclaimer) {
                        const disclaimerDiv = document.createElement("div");
                        disclaimerDiv.className = "ai-disclaimer";

                        const icon = document.createElement("i");
                        icon.className = "bi bi-info-circle";
                        icon.setAttribute("aria-hidden", "true");

                        const text = document.createElement("span");
                        text.textContent = msg.disclaimer;

                        disclaimerDiv.appendChild(icon);
                        disclaimerDiv.appendChild(text);
                        messageDiv.appendChild(disclaimerDiv);
                    }

                    const actionsDiv = document.createElement("div");
                    actionsDiv.className = "ai-actions";

                    const copyButton = document.createElement("button");
                    copyButton.type = "button";
                    copyButton.className = "ai-action-btn";
                    copyButton.dataset.copyIndex = String(index);
                    copyButton.innerHTML = '<i class="bi bi-copy" aria-hidden="true"></i> Copy answer';
                    actionsDiv.appendChild(copyButton);
                    messageDiv.appendChild(actionsDiv);

                    if (index === messages.length - 1 && msg.content) {
                        const followups = getFollowUpSuggestions(msg.content);
                        const followupSection = document.createElement("div");
                        followupSection.className = "ai-followups";

                        const title = document.createElement("div");
                        title.className = "ai-followups-title";
                        title.textContent = "You may also ask:";

                        const list = document.createElement("div");
                        list.className = "ai-followup-list";

                        followups.forEach(question => {
                            const button = document.createElement("button");
                            button.type = "button";
                            button.className = "ai-followup-chip";
                            button.dataset.question = question;
                            button.textContent = question;
                            list.appendChild(button);
                        });

                        followupSection.appendChild(title);
                        followupSection.appendChild(list);
                        messageDiv.appendChild(followupSection);
                    }
                }

                row.appendChild(avatar);
                row.appendChild(messageDiv);
                chatBox.appendChild(row);
            });

            const visibleMessages = messages.filter(message => !message.isTyping).length;
            messageCount.textContent = `${visibleMessages} ${visibleMessages === 1 ? "message" : "messages"}`;

            if (forceBottom || wasNearBottom) {
                scrollToLatest(false);
            }

            updateScrollButton();
        }

        function isNearBottom() {
            return chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 100;
        }

        function scrollToLatest(smooth = true) {
            chatBox.scrollTo({
                top: chatBox.scrollHeight,
                behavior: smooth && !reduceMotion ? "smooth" : "auto"
            });
        }

        function updateScrollButton() {
            scrollLatestBtn.classList.toggle("visible", !isNearBottom());
        }

        function setQuestion(question) {
            if (isSending) return;
            userInput.value = String(question || "").slice(0, MAX_MESSAGE_LENGTH);
            autoResizeTextarea();
            updateComposerState();
            userInput.focus();
        }

        async function copyToClipboard(text) {
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text || "");
                } else {
                    const temporary = document.createElement("textarea");
                    temporary.value = text || "";
                    temporary.style.position = "fixed";
                    temporary.style.opacity = "0";
                    document.body.appendChild(temporary);
                    temporary.select();
                    document.execCommand("copy");
                    temporary.remove();
                }

                showStatus("Answer copied to your clipboard.", "success");
            } catch (error) {
                showStatus("The answer could not be copied.", "error");
            }
        }

        function setLoadingState(loading) {
            isSending = loading;
            sendBtn.disabled = loading || !userInput.value.trim();
            clearBtn.disabled = loading;
            userInput.disabled = loading;

            document.querySelectorAll(".ai-question-chip, .ai-followup-chip").forEach(button => {
                button.disabled = loading;
            });

            sendBtn.innerHTML = loading
                ? '<span>Sending</span><i class="bi bi-arrow-repeat" aria-hidden="true"></i>'
                : '<span>Send message</span><i class="bi bi-send-fill" aria-hidden="true"></i>';
        }

        function showStatus(message = "", type = "") {
            statusText.className = `ai-status${type ? ` ${type}` : ""}`;
            statusText.textContent = message;
        }

        function showTypingStatus() {
            statusText.className = "ai-status";
            statusText.innerHTML = `
                <span class="ai-typing">
                    AmoraCare is preparing a response
                    <span class="ai-dots" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </span>
                </span>
            `;
        }

        function updateComposerState() {
            const length = userInput.value.length;
            characterCount.textContent = `${length} / ${MAX_MESSAGE_LENGTH}`;
            characterCount.classList.toggle("near-limit", length >= 1750 && length <= MAX_MESSAGE_LENGTH);
            characterCount.classList.toggle("over-limit", length > MAX_MESSAGE_LENGTH);
            sendBtn.disabled = isSending || !userInput.value.trim() || length > MAX_MESSAGE_LENGTH;
        }

        function autoResizeTextarea() {
            userInput.style.height = "auto";
            userInput.style.height = `${Math.min(userInput.scrollHeight, 190)}px`;
        }

        function sleep(ms) {
            return new Promise(resolve => window.setTimeout(resolve, ms));
        }

        async function typeAssistantReply(fullText, sources = [], disclaimer = null) {
            const finalText = fullText || "Sorry, I could not generate a clear answer right now.";
            const messageIndex = messages.length;

            messages.push({
                role: "assistant",
                content: "",
                sources: [],
                disclaimer: null,
                isTyping: true,
                createdAt: new Date().toISOString()
            });

            renderChat();

            if (reduceMotion || finalText.length > 1800) {
                messages[messageIndex].content = finalText;
            } else {
                const pieces = finalText.match(/\S+\s*/g) || [finalText];

                for (const piece of pieces) {
                    messages[messageIndex].content += piece;
                    renderChat();
                    await sleep(/[.!?]\s*$/.test(piece) ? 65 : 16);
                }
            }

            messages[messageIndex] = {
                role: "assistant",
                content: finalText,
                sources: Array.isArray(sources) ? sources : [],
                disclaimer: disclaimer || null,
                isTyping: false,
                createdAt: new Date().toISOString()
            };

            renderChat();
        }

        async function sendMessage() {
            const text = userInput.value.trim();

            if (!text || isSending) return;

            if (text.length > MAX_MESSAGE_LENGTH) {
                showStatus(`Please keep your question under ${MAX_MESSAGE_LENGTH} characters.`, "error");
                return;
            }

            messages.push({
                role: "user",
                content: text,
                sources: [],
                disclaimer: null,
                isTyping: false,
                createdAt: new Date().toISOString()
            });

            userInput.value = "";
            autoResizeTextarea();
            updateComposerState();
            renderChat();
            setLoadingState(true);
            showTypingStatus();

            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

            try {
                const response = await fetch(CHAT_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ message: text }),
                    signal: controller.signal
                });

                let data = {};

                try {
                    data = await response.json();
                } catch (error) {
                    data = {};
                }

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        data.details ||
                        "The AI legal guidance request could not be completed."
                    );
                }

                showStatus();

                await typeAssistantReply(
                    data.reply || "No response was received.",
                    data.sources || [],
                    data.disclaimer || null
                );
            } catch (error) {
                const timedOut = error?.name === "AbortError";
                const detail = timedOut
                    ? "The request took too long. Please try a shorter or more specific question."
                    : error.message;

                showStatus(detail, "error");

                await typeAssistantReply(
                    timedOut
                        ? "I could not complete the request within the available time. Please try again with a more specific question, or contact AmoraCare staff if the concern is urgent."
                        : "Sorry, I had trouble processing your request. Please try again, or contact AmoraCare staff if the concern is urgent.",
                    [],
                    null
                );
            } finally {
                window.clearTimeout(timeoutId);
                setLoadingState(false);
                updateComposerState();
                userInput.focus();
            }
        }

        async function clearChat() {
            if (isSending) return;

            const shouldClear = window.confirm("Clear your conversation?");
            if (!shouldClear) return;

            try {
                const response = await fetch(CLEAR_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                        "Accept": "application/json"
                    }
                });

                if (!response.ok) {
                    throw new Error("The server could not clear the conversation.");
                }

                messages = [{
                    ...defaultMessage,
                    content: "Your conversation has been cleared. Choose a different initial request, or type your own adoption-related question.",
                    createdAt: new Date().toISOString()
                }];

                renderChat();
                showStatus("Conversation cleared.", "success");
            } catch (error) {
                showStatus("The conversation could not be cleared. Please try again.", "error");
            }
        }

        document.addEventListener("click", event => {
            const questionButton = event.target.closest("[data-question]");

            if (questionButton) {
                setQuestion(questionButton.dataset.question);

                if (questionButton.dataset.sendImmediately === "true") {
                    sendMessage();
                }

                return;
            }

            const copyButton = event.target.closest("[data-copy-index]");

            if (copyButton) {
                const index = Number(copyButton.dataset.copyIndex);
                const message = messages[index];

                if (message?.content) {
                    copyToClipboard(message.content);
                }
            }
        });

        sendBtn.addEventListener("click", sendMessage);
        clearBtn.addEventListener("click", clearChat);
        scrollLatestBtn.addEventListener("click", () => scrollToLatest(true));

        userInput.addEventListener("input", () => {
            autoResizeTextarea();
            updateComposerState();
            showStatus();
        });

        userInput.addEventListener("keydown", event => {
            if (event.key === "Enter" && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });

        chatBox.addEventListener("scroll", updateScrollButton, { passive: true });

        renderChat();
        autoResizeTextarea();
        updateComposerState();
    </script>
@endsection
