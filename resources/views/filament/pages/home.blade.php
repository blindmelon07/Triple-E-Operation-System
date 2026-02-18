<x-filament-panels::page>
    @php
        $cards = $this->getCards();
        $user  = auth()->user();
        $now   = now()->timezone('Asia/Manila');
    @endphp

    {{-- Greeting Banner --}}
    <div style="
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    ">
        <div>
            <p style="color: #bfdbfe; font-size: 0.875rem; margin-bottom: 0.25rem;">
                {{ $now->format('l, F d Y') }}
            </p>
            <h2 style="color: #ffffff; font-size: 1.5rem; font-weight: 700; margin: 0;">
                {{ $this->getGreeting() }}, {{ $user->name }}!
            </h2>
            <p style="color: #bfdbfe; font-size: 0.875rem; margin-top: 0.25rem;">
                Here are the modules you have access to.
            </p>
        </div>
        <div style="text-align: right;">
            <p style="color: #ffffff; font-size: 2rem; font-weight: 700; margin: 0; font-variant-numeric: tabular-nums;"
               id="home-clock">{{ $now->format('h:i A') }}</p>
            <p style="color: #bfdbfe; font-size: 0.75rem; margin-top: 0.25rem;">
                {{ implode(' • ', array_map(fn($r) => ucwords(str_replace('_', ' ', $r->name)), $user->roles->all())) ?: 'No Role' }}
            </p>
        </div>
    </div>

    {{-- Module Cards Grid --}}
    @if(count($cards) === 0)
        <div style="text-align: center; padding: 3rem; color: #64748b;">
            <x-filament::icon icon="heroicon-o-lock-closed" style="width: 3rem; height: 3rem; margin: 0 auto 1rem;" />
            <p style="font-size: 1.125rem; font-weight: 600;">No modules available</p>
            <p style="font-size: 0.875rem;">Contact your administrator to get access.</p>
        </div>
    @else
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        ">
            @foreach($cards as $card)
                <a href="{{ $card['url'] }}" style="text-decoration: none;" wire:navigate>
                    <div style="
                        background: {{ $card['bg'] }};
                        border: 1px solid {{ $card['border'] }};
                        border-radius: 12px;
                        padding: 1.25rem;
                        height: 100%;
                        display: flex;
                        flex-direction: column;
                        gap: 0.75rem;
                        transition: transform 0.15s ease, box-shadow 0.15s ease;
                        cursor: pointer;
                    "
                    onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                    >
                        {{-- Icon --}}
                        <div style="
                            width: 2.75rem;
                            height: 2.75rem;
                            background: {{ $card['color'] }}22;
                            border-radius: 10px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">
                            <x-filament::icon
                                :icon="$card['icon']"
                                style="width: 1.5rem; height: 1.5rem; color: {{ $card['color'] }};"
                            />
                        </div>

                        {{-- Text --}}
                        <div style="flex: 1;">
                            <p style="
                                font-size: 0.9375rem;
                                font-weight: 700;
                                color: #1e293b;
                                margin: 0 0 0.25rem;
                            ">{{ $card['title'] }}</p>
                            <p style="
                                font-size: 0.8125rem;
                                color: #64748b;
                                margin: 0;
                                line-height: 1.4;
                            ">{{ $card['description'] }}</p>
                        </div>

                        {{-- Arrow --}}
                        <div style="
                            display: flex;
                            align-items: center;
                            gap: 0.25rem;
                            font-size: 0.75rem;
                            font-weight: 600;
                            color: {{ $card['color'] }};
                        ">
                            Open
                            <x-filament::icon
                                icon="heroicon-o-arrow-right"
                                style="width: 0.875rem; height: 0.875rem;"
                            />
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Live clock script --}}
    <script>
        (function () {
            function pad(n) { return String(n).padStart(2, '0'); }
            function tick() {
                var el = document.getElementById('home-clock');
                if (!el) return;
                var now = new Date();
                var h = now.getHours();
                var m = now.getMinutes();
                var ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                el.textContent = pad(h) + ':' + pad(m) + ' ' + ampm;
            }
            tick();
            setInterval(tick, 30000);
        })();
    </script>
</x-filament-panels::page>
