{{--
    $activeTab is either 'stock' or 'ledger'.

    Deliberately flat (no bg-white/border/shadow card wrapper) to match the
    reference screenshot — just a full-width hairline under both tabs, with
    the active tab's own border overlapping it in amber.

    Anti-"jump" details, all load-bearing:
    - Both tabs share the exact same font-weight (font-semibold) and padding
      in EVERY state. Nothing about a tab's own box size changes when it
      becomes active/inactive — only color does. A font-weight or padding
      difference between states is the #1 cause of a tab bar visibly
      shifting width when you switch, so this is deliberately never varied.
    - border-b-2 is present on both tabs at all times; only its color
      toggles between transparent and amber-500. This reserves the same 2px
      of vertical space whether a tab is active or not, so nothing reflows
      vertically either.
    - -mb-px pulls each tab's own border up by 1px so it sits exactly on
      top of the shared border-b-gray-200 line on the parent, instead of
      the active tab's underline appearing to float above or double up
      with the hairline beneath it.
--}}
<div class="mb-4">
    <div class="flex gap-6 border-b border-gray-200">
        <a href="{{ route('admin.inventory.index') }}"
            class="pb-3 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $activeTab === 'stock' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-800 hover:text-gray-900' }}">
            Current Stock
        </a>
        <a href="{{ route('admin.inventory.ledger') }}"
            class="pb-3 text-sm font-semibold border-b-2 -mb-px transition-colors {{ $activeTab === 'ledger' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-800 hover:text-gray-900' }}">
            Stock Movement Ledger
        </a>
    </div>
</div>