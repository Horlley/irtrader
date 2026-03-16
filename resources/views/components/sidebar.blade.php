<div class="sidebar" x-data="{open:true}" :class="{'collapsed':!open}">

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
            <a href="/taxes"
                class="{{ request()->is('taxes*') ? 'active' : '' }}">
                <span>💰</span>
                Impostos
            </a>
        </li>

        <li>
            <a href="/darf"
                class="{{ request()->is('darf*') ? 'active' : '' }}">
                <span>📄</span>
                DARF
            </a>
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