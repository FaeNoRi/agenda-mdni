@if($notifications->isNotEmpty())
<div id="notifTicker" class="mb-3 pt-2">

    <div id="notifTickerBar"
         class="d-flex align-items-center gap-2 border rounded px-3 py-2 bg-white"
         style="cursor:pointer;"
         role="button"
         tabindex="0"
         aria-expanded="false"
         aria-controls="notifTickerPanel">

        <i id="notifTickerIcon" class="ti ti-clock text-blue" style="font-size:18px;flex-shrink:0;"></i>

        <span id="notifTickerBadge" class="badge bg-blue-lt flex-shrink-0">Changement</span>

        <div class="flex-fill overflow-hidden">
            <div id="notifTickerTrack" class="marquee-track">
                <span id="notifTickerText" class="small text-truncate d-block"></span>
            </div>
        </div>

        <span id="notifTickerCount" class="text-muted small flex-shrink-0"></span>
        <i id="notifTickerChevron" class="ti ti-chevron-down text-muted flex-shrink-0"
           style="transition: transform .2s ease;"></i>
    </div>

    <div id="notifTickerPanel" class="border border-top-0 rounded-bottom bg-white" style="max-height:0; overflow:hidden; transition:max-height .3s ease;">

        @php
            $horaires = $notifications->where('type', 'horaire');
            $conges   = $notifications->where('type', 'conge');
        @endphp

        @if($horaires->isNotEmpty())
            <div class="px-3 pt-2 pb-1 text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.03em; font-weight:700;">
                Changements de planning
            </div>
            <div class="list-group list-group-flush">
                @foreach($horaires as $n)
                    <div class="list-group-item py-2" style="border-left:3px solid #206bc4;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-clock text-blue" style="font-size:16px;"></i>
                            <div class="flex-fill">
                                <div class="fw-bold small">{{ $n['user_name'] }}</div>
                                <div class="text-muted small">{{ $n['summary'] }}</div>
                            </div>
                            <span class="badge bg-blue-lt flex-shrink-0">{{ $n['badge_label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($conges->isNotEmpty())
            <div class="px-3 pt-2 pb-1 text-uppercase text-muted" style="font-size:.7rem; letter-spacing:.03em; font-weight:700;">
                Congés
            </div>
            <div class="list-group list-group-flush mb-1">
                @foreach($conges as $n)
                    <div class="list-group-item py-2" style="border-left:3px solid #f76707;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-calendar-x text-orange" style="font-size:16px;"></i>
                            <div class="flex-fill">
                                <div class="fw-bold small">{{ $n['user_name'] }}</div>
                                <div class="text-muted small">{{ $n['summary'] }}</div>
                            </div>
                            <span class="badge bg-orange-lt flex-shrink-0">{{ $n['badge_label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

<script>
(function () {
    const items = @json($notifications->values());
    if (!items.length) return;

    let idx = 0;
    let timer = null;
    let expanded = false;

    const bar     = document.getElementById('notifTickerBar');
    const track   = document.getElementById('notifTickerTrack');
    const textEl  = document.getElementById('notifTickerText');
    const iconEl  = document.getElementById('notifTickerIcon');
    const badgeEl = document.getElementById('notifTickerBadge');
    const countEl = document.getElementById('notifTickerCount');
    const panel   = document.getElementById('notifTickerPanel');
    const chevron = document.getElementById('notifTickerChevron');

    function render(item) {
        textEl.textContent = item.user_name + ' — ' + item.summary;
        countEl.textContent = (idx + 1) + '/' + items.length;

        const isConge = item.type === 'conge';
        iconEl.className = 'ti ' + (isConge ? 'ti-calendar-x text-orange' : 'ti-clock text-blue');
        badgeEl.className = 'badge flex-shrink-0 ' + (isConge ? 'bg-orange-lt' : 'bg-blue-lt');
        badgeEl.textContent = item.badge_label;
    }

    function next() {
        track.style.transition = 'transform .3s ease, opacity .3s ease';
        track.style.opacity = '0';
        track.style.transform = 'translateY(-6px)';
        setTimeout(() => {
            idx = (idx + 1) % items.length;
            render(items[idx]);
            track.style.transition = 'none';
            track.style.transform = 'translateY(6px)';
            requestAnimationFrame(() => {
                track.style.transition = 'transform .3s ease, opacity .3s ease';
                track.style.opacity = '1';
                track.style.transform = 'translateY(0)';
            });
        }, 280);
    }

    function start() {
        stop();
        if (items.length > 1) {
            timer = setInterval(next, 3500);
        }
    }
    function stop() {
        if (timer) clearInterval(timer);
        timer = null;
    }

    function toggle() {
        expanded = !expanded;
        bar.setAttribute('aria-expanded', String(expanded));
        if (expanded) {
            panel.style.maxHeight = panel.scrollHeight + 'px';
            chevron.style.transform = 'rotate(180deg)';
            stop();
        } else {
            panel.style.maxHeight = '0';
            chevron.style.transform = 'rotate(0deg)';
            start();
        }
    }

    bar.addEventListener('click', toggle);
    bar.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
    });
    bar.addEventListener('mouseenter', stop);
    bar.addEventListener('mouseleave', () => { if (!expanded) start(); });

    render(items[0]);
    start();
})();
</script>
@endif
