<button id="amor-a11y-trigger" class="amor-a11y-trigger" type="button" aria-haspopup="dialog" aria-controls="amor-a11y-dialog" aria-expanded="false" hidden>
    <svg viewBox="0 0 24 24" width="25" height="25" aria-hidden="true" focusable="false"><circle cx="12" cy="4" r="2.25" fill="currentColor"/><path d="M4 8.25 12 10l8-1.75M12 10v5m0 0-4 6m4-6 4 6" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span>Accessibility</span>
</button>
<dialog id="amor-a11y-dialog" class="amor-a11y-panel" aria-labelledby="amor-a11y-title" aria-describedby="amor-a11y-intro">
    <div class="amor-a11y-panel-header">
        <h2 id="amor-a11y-title">Make yourself comfortable</h2>
        <button id="amor-a11y-close" class="amor-a11y-close" type="button" aria-label="Close accessibility preferences" autofocus><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"/></svg></button>
    </div>
    <div class="amor-a11y-panel-content">
        <p id="amor-a11y-intro">Adjust how this website looks and feels. Your choices are saved in this browser when storage is available.</p>
        <div class="amor-a11y-text-controls" role="group" aria-labelledby="amor-a11y-text-label">
            <div class="amor-a11y-text-heading"><h3 id="amor-a11y-text-label">Page text size</h3><output id="amor-a11y-text-value" aria-label="Current text size">100%</output></div>
            <div class="amor-a11y-text-actions">
                <button id="amor-a11y-increase" type="button">Increase text <span aria-hidden="true">A+</span></button>
                <button id="amor-a11y-reset-text" type="button" disabled>Reset text</button>
            </div>
        </div>
        <div class="amor-a11y-options" role="group" aria-label="Reading preferences">
            @foreach ([
                'lineSpacing' => 'More line spacing',
                'letterSpacing' => 'More letter spacing',
                'highContrast' => 'High contrast',
                'underline' => 'Underline links',
                'reduceMotion' => 'Reduce motion',
                'readingGuide' => 'Reading guide',
            ] as $preference => $label)
                <button type="button" class="amor-a11y-option" data-amor-preference="{{ $preference }}" aria-pressed="false"><span>{{ $label }}</span><span class="amor-a11y-state" aria-hidden="true">Off</span></button>
            @endforeach
        </div>
        <p id="amor-a11y-guide-help" class="amor-a11y-hint" hidden>Close this panel to use the reading guide. Move it with your pointer, or hold Alt and press the Up or Down arrow key. On touch screens, touch the page to position it.</p>
        <p class="amor-a11y-hint">Your device’s reduced-motion preference is always respected.</p>
        <div class="amor-a11y-panel-footer">
            <button id="amor-a11y-reset-all" type="button">Reset all preferences</button>
            <a href="#accessibility">Accessibility Statement <span aria-hidden="true">↗</span></a>
        </div>
        <p id="amor-a11y-status" class="amor-a11y-visually-hidden" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
</dialog>
<div id="amor-reading-guide" aria-hidden="true" hidden></div>
