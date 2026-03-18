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
            <a href="/dashboard"
                class="{{ request()->is('dashboard*') ? 'active' : '' }}">
                <span>📊</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="/trades"
                class="{{ request()->is('trades*') ? 'active' : '' }}">
                <span>📈</span>
                Operações
            </a>
        </li>

        <li>
            <a href="/imports"
                class="{{ request()->is('imports*') ? 'active' : '' }}">
                <span>📂</span>
                Importar Notas
            </a>
        </li>

        <li>
            <a href="/"
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
                    <a href="{{ route('tax.index') }}"
                        class="{{ request()->is('tax') ? 'active' : '' }}">
                        📊 Apuração Mensal
                    </a>
                </li>

                <li>
                    <a href="{{ route('tax.report') }}"
                        class="{{ (request()->is('tax/report') || request()->is('tax/report/*')) && !request()->is('tax/report-ir*') ? 'active' : '' }}">
                        📈 Resultado por Mercado
                    </a>
                </li>

                <li>
                    <a href="{{ url('/tax/report-ir/' . date('Y')) }}"
                        class="{{ request()->is('tax/report-ir*') ? 'active' : '' }}">
                        🧾 Relatório IR (Receita)
                    </a>
                </li>

                <li>
                    <a href="{{ route('tax.annual', date('Y')) }}"
                        class="{{ request()->is('tax/annual*') ? 'active' : '' }}">
                        📄 Relatório Anual
                    </a>
                </li>

                <li>
                    <a href="{{ route('darfs.index') }}"
                        class="{{ request()->is('darfs*') ? 'active' : '' }}">
                        🧾 DARFs
                    </a>
                </li>

            </ul>

        </li>

        <li>
            <a href="/settings"
                class="{{ request()->is('settings*') ? 'active' : '' }}">
                <span>⚙️</span>
                Configurações
            </a>
        </li>

    </ul>

</div>