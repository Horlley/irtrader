<div class="sidebar"
    x-data="{
        open: true,
        taxOpen: {{ request()->is('tax*') || request()->is('darfs*') || request()->is('tax/report-ir*') ? 'true' : 'false' }}
    }"
    :class="{'collapsed':!open}">

    <div class="logo">
        IR Trader
    </div>

    <ul class="menu">

        <li>
            <a href="/dashboard" data-ajax-link
                class="{{ request()->is('dashboard*') ? 'active' : '' }}">
                <span>📊</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="/trades" data-ajax-link
                class="{{ request()->is('trades*') ? 'active' : '' }}">
                <span>📈</span>
                Operações
            </a>
        </li>

        <li>
            <a href="/imports" data-ajax-link
                class="{{ request()->is('imports*') ? 'active' : '' }}">
                <span>📂</span>
                Importar Notas
            </a>
        </li>

        <li>
            <a href="/" data-ajax-link
                class="{{ request()->path() == '/' ? 'active' : '' }}">
                <span>⬆️</span>
                Importar em Massa
            </a>
        </li>

        <!-- IMPOSTOS -->
        <li>

            <a href="#"
                @click.prevent="taxOpen = !taxOpen"
                class="{{ request()->is('tax*') || request()->is('darfs*') || request()->is('tax/report-ir*') ? 'active' : '' }}">

                <span>💰</span>
                Impostos

            </a>

            <ul x-show="taxOpen" x-transition style="padding-left:20px">

                <li>
                    <a href="{{ route('tax.index') }}" data-ajax-link
                        class="{{ request()->is('tax') ? 'active' : '' }}">
                        📊 Apuração Mensal
                    </a>
                </li>

                <li>
                    <a href="{{ route('tax.report') }}" data-ajax-link
                        class="{{ (request()->is('tax/report') || request()->is('tax/report/*')) && !request()->is('tax/report-ir*') ? 'active' : '' }}">
                        📈 Resultado por Mercado
                    </a>
                </li>

                <li>
                    <a href="{{ route('tax.brokerage-notes') }}" data-ajax-link
                        class="{{ request()->is('tax/brokerage-notes*') ? 'active' : '' }}">
                        📈 Notas corretagem
                    </a>
                </li>

                <li>
                    <a href="{{ url('/tax/report-ir/' . date('Y')) }}" data-ajax-link
                        class="{{ request()->is('tax/report-ir*') ? 'active' : '' }}">
                        🧾 Relatório IR (Receita)
                    </a>
                </li>

                <li>
                    <a href="{{ route('tax.annual', date('Y')) }}" data-ajax-link
                        class="{{ request()->is('tax/annual*') ? 'active' : '' }}">
                        📄 Relatório Anual
                    </a>
                </li>

                <li>
                    <a href="{{ route('darfs.index') }}" data-ajax-link
                        class="{{ request()->is('darfs*') ? 'active' : '' }}">
                        🧾 DARFs
                    </a>
                </li>

            </ul>

        </li>

        <li>
            <a href="/settings" data-ajax-link
                class="{{ request()->is('settings*') ? 'active' : '' }}">
                <span>⚙️</span>
                Configurações
            </a>
        </li>

    </ul>

</div>
