@props([
    'title' => 'Цены по видам профиля',
    'note' => 'В таблицах указаны цены на популярные размеры рольставен с ПИМ, щеколдами, самозамером и самовывозом.',
])

@php
    $profiles = [
        'RH45N' => [
            'widths' => [500, 1000, 1500, 2000, 2300, 2500, 3000],
            'rows' => [
                [500, 7895, 11264, 14840, 18868, 20891, 22814, 26019],
                [1000, 12710, 14796, 20099, 25263, 28069, 30130, 34978],
                [1500, 13050, 19540, 26100, 32359, 26624, 39613, 45484],
                [2000, 15615, 23832, 31500, 39792, 44257, 47439, 55088],
                [2300, 16968, 21946, 35115, 43815, 48845, 52030, 63718],
                [2500, null, 27835, 37550, 48462, 51923, 55670, null],
                [3000, null, 32738, 44353, 55326, 61250, null, null],
                [3500, null, 36240, 49111, 61063, null, null, null],
            ],
        ],
        'RH58N' => [
            'widths' => [500, 1000, 1500, 2000, 2500, 3000],
            'rows' => [
                [500, null, 14963, 19103, 23244, 27383, 31523],
                [1000, null, 18831, 24401, 29971, 35541, 41111],
                [1500, null, 22897, 29873, 36846, 43821, 50730],
                [2000, null, 27926, 36852, 45779, 54641, 63567],
                [2500, null, 31792, 42149, 52442, 62797, 74922],
                [3000, null, 36775, 48537, 60366, 73962, 85791],
                [3500, null, 42179, 55464, 70580, 83930, 97281],
            ],
        ],
        'RH77M' => [
            'widths' => [500, 1000, 1500, 2000, 2500, 3000, 4000],
            'rows' => [
                [500, null, 29469, 36570, 43603, 50637, 57670, null],
                [1000, null, 36702, 46271, 55841, 65341, 74911, null],
                [1500, null, 43758, 55502, 67176, 80779, 93078, null],
                [2000, null, 51140, 65350, 81857, 96230, 110605, null],
                [2500, null, 57863, 74247, 92927, 109474, 125755, null],
                [3000, null, 65537, 86796, 106018, 124983, 144204, null],
                [3500, null, 72712, 96508, 118000, 139766, 167463, null],
            ],
        ],
    ];

    $formatPrice = static function (?int $price): string {
        return $price ? number_format($price, 0, '', ' ') . ' ₽' : '-';
    };
@endphp

<section class="s-rollets-prices wrapper">
    <h2 class="s-rollets-prices__title title">
        <span>{{ $title }}</span>
        <svg width="114" height="35" viewBox="0 0 114 35" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M112 23.275C1.84952 -10.6834 -7.36586 1.48086 7.50443 32.9053" stroke="currentColor" stroke-width="4" stroke-miterlimit="3.8637" stroke-linecap="round"></path>
        </svg>
    </h2>

    <p class="s-rollets-prices__note">
        {{ $note }}
    </p>

    <div class="s-rollets-prices__tabs" data-profile-price-tabs>
        <div class="s-rollets-prices__nav" role="tablist" aria-label="Виды профиля">
            @foreach ($profiles as $profileName => $profile)
                <button
                    type="button"
                    class="s-rollets-prices__tab {{ $loop->first ? 'active' : '' }}"
                    data-profile-price-tab="{{ $loop->index }}"
                    role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                >
                    {{ $profileName }}
                </button>
            @endforeach
        </div>

        <div class="s-rollets-prices__panels">
            @foreach ($profiles as $profileName => $profile)
                <div class="s-rollets-prices__panel {{ $loop->first ? 'active' : '' }}" data-profile-price-panel="{{ $loop->index }}" role="tabpanel">
                    <div class="s-rollets-prices__table-wrap">
                        <table class="s-rollets-prices__table">
                            <thead>
                                <tr>
                                    <th class="s-rollets-prices__corner">В/Ш</th>
                                    @foreach ($profile['widths'] as $width)
                                        <th>{{ $width }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($profile['rows'] as $row)
                                    <tr>
                                        <th>{{ $row[0] }}</th>
                                        @foreach (array_slice($row, 1) as $price)
                                            <td>{{ $formatPrice($price) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="s-rollets-prices__option">Доп. опция: электропривод +4 500 ₽</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .s-rollets-prices {
        padding-top: 60px;
    }

    .s-rollets-prices__title {
        margin-bottom: 18px;
    }

    .s-rollets-prices__note {
        max-width: 980px;
        margin: 0 0 26px;
        padding: 0;
        background: transparent;
        color: #555;
        font-size: 17px;
        line-height: 1.5;
    }

    .s-rollets-prices__nav {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
        gap: 10px;
        margin-bottom: 28px;
    }

    .s-rollets-prices__tab {
        min-width: 118px;
        min-height: 52px;
        padding: 12px 20px;
        border: 1px solid #0989ff;
        border-radius: 6px;
        background: #fff;
        color: #0989ff;
        cursor: pointer;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.2;
        transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }

    .s-rollets-prices__tab:hover,
    .s-rollets-prices__tab.active {
        border-color: #0989ff;
        background: #0989ff;
        color: #fff;
    }

    .s-rollets-prices__panel {
        display: none;
    }

    .s-rollets-prices__panel.active {
        display: block;
    }

    .s-rollets-prices__table-wrap {
        overflow-x: auto;
        border: 1px solid #d6e9ff;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(9, 137, 255, 0.06);
    }

    .s-rollets-prices__table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        color: #333;
        font-size: 14px;
        line-height: 1.2;
        text-align: center;
    }

    .s-rollets-prices__table th,
    .s-rollets-prices__table td {
        padding: 7px 10px;
        border-bottom: 1px solid #e0e0e0;
        white-space: nowrap;
    }

    .s-rollets-prices__table thead th {
        border-bottom: 2px solid #0989ff;
        color: #333;
        font-weight: 700;
    }

    .s-rollets-prices__table tbody th {
        color: #111;
        font-weight: 700;
    }

    .s-rollets-prices__corner {
        color: #0989ff !important;
    }

    .s-rollets-prices__option {
        margin-top: 14px;
        color: #555;
        font-size: 15px;
    }

    @media (max-width: 767px) {
        .s-rollets-prices {
            padding-top: 42px;
        }

        .s-rollets-prices__nav {
            justify-content: flex-start;
            gap: 8px;
            margin-bottom: 22px;
        }

        .s-rollets-prices__tab {
            flex: 1 1 calc(33.333% - 8px);
            min-width: 92px;
            min-height: 44px;
            padding: 10px 12px;
            font-size: 14px;
        }

        .s-rollets-prices__note {
            font-size: 15px;
        }

        .s-rollets-prices__table {
            min-width: 640px;
            font-size: 13px;
        }

        .s-rollets-prices__table th,
        .s-rollets-prices__table td {
            padding: 7px 8px;
        }
    }

    @media (max-width: 420px) {
        .s-rollets-prices__tab {
            flex-basis: calc(50% - 8px);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-profile-price-tabs]').forEach(function(wrapper) {
            const tabs = wrapper.querySelectorAll('[data-profile-price-tab]');
            const panels = wrapper.querySelectorAll('[data-profile-price-panel]');

            tabs.forEach(function(tab, index) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(item) {
                        item.classList.remove('active');
                        item.setAttribute('aria-selected', 'false');
                    });

                    panels.forEach(function(panel) {
                        panel.classList.remove('active');
                    });

                    tab.classList.add('active');
                    tab.setAttribute('aria-selected', 'true');

                    if (panels[index]) {
                        panels[index].classList.add('active');
                    }
                });
            });
        });
    });
</script>
