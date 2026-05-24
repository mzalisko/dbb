<x-layouts.app>
    <div style="max-width:960px;margin:0 auto;padding:40px 24px;">
        <h1 style="font-size:28px;margin-bottom:8px;">Design System</h1>
        <p style="margin-bottom:40px;">DataBridge CRM component library &mdash; Loft Quiet theme.</p>

        {{-- ============================================================ --}}
        {{-- BUTTONS --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Buttons</h2>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <x-ui.button variant="primary">Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="danger">Danger</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="accent">Accent</x-ui.button>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-top:12px;">
                <x-ui.button variant="primary" size="sm">Small</x-ui.button>
                <x-ui.button variant="primary">Default</x-ui.button>
                <x-ui.button variant="primary" size="lg">Large</x-ui.button>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- INPUT --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Input</h2>
            <div style="max-width:400px;">
                <x-ui.input label="Email" name="demo-email" placeholder="you@example.com" />
            </div>
            <div style="max-width:400px;margin-top:16px;">
                <x-ui.input label="With Error" name="demo-err" error="This field is required" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- TEXTAREA --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Textarea</h2>
            <div style="max-width:400px;">
                <x-ui.textarea label="Message" name="demo-msg" placeholder="Type your message..." />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- SELECT --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Select</h2>
            <div style="max-width:400px;">
                <x-ui.select label="Country" name="demo-country" placeholder="Choose...">
                    <option value="ua">Ukraine</option>
                    <option value="us">United States</option>
                    <option value="de">Germany</option>
                </x-ui.select>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- CHECKBOX --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Checkbox</h2>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <x-ui.checkbox label="Accept terms and conditions" name="demo-terms" />
                <x-ui.checkbox label="Subscribe to newsletter" name="demo-news" :checked="true" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- RADIO --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Radio</h2>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <x-ui.radio label="Monthly billing" name="demo-billing" value="monthly" :checked="true" />
                <x-ui.radio label="Yearly billing" name="demo-billing" value="yearly" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- CARD --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Card</h2>
            <x-ui.card>
                <x-slot:header>Card Header</x-slot:header>
                This is a card with header and footer slots.
                <x-slot:footer>
                    <x-ui.button variant="primary" size="sm">Save</x-ui.button>
                </x-slot:footer>
            </x-ui.card>
        </section>

        {{-- ============================================================ --}}
        {{-- TABS --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Tabs</h2>
            <x-ui.tabs>
                <x-ui.tab :active="true" href="#">Overview</x-ui.tab>
                <x-ui.tab href="#" :count="12">Sites</x-ui.tab>
                <x-ui.tab href="#">Settings</x-ui.tab>
            </x-ui.tabs>
        </section>

        {{-- ============================================================ --}}
        {{-- PILL / BADGE --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Pill / Badge</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <x-ui.pill>Default</x-ui.pill>
                <x-ui.pill status="ok">Active</x-ui.pill>
                <x-ui.pill status="warn">Pending</x-ui.pill>
                <x-ui.pill status="bad">Error</x-ui.pill>
                <x-ui.pill status="info">Info</x-ui.pill>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- AVATAR --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Avatar</h2>
            <div style="display:flex;gap:12px;align-items:center;">
                <x-ui.avatar initials="jd" />
                <x-ui.avatar initials="ab" size="lg" />
                <x-ui.avatar initials="sq" :square="true" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- MODAL --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Modal</h2>
            <p style="margin-bottom:8px;font-size:13px;color:var(--ink-5);">
                <strong>Standalone Alpine:</strong> <code>@click="$dispatch('open-modal', 'demo')"</code><br>
                <strong>Livewire:</strong> <code>$this->dispatch('open-modal', 'demo')</code>
            </p>
            <x-ui.button variant="secondary" @click="$dispatch('open-modal', 'demo-modal')">Open Modal</x-ui.button>
            <x-ui.modal name="demo-modal">
                <x-slot:title>Example Modal</x-slot:title>
                <p>This is modal body content. Close with Escape or click outside.</p>
                <x-slot:footer>
                    <x-ui.button variant="ghost" @click="$dispatch('close-modal', 'demo-modal')">Cancel</x-ui.button>
                    <x-ui.button variant="primary">Confirm</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>
        </section>

        {{-- ============================================================ --}}
        {{-- DROPDOWN --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Dropdown</h2>
            <p style="margin-bottom:8px;font-size:13px;color:var(--ink-5);">
                <strong>Livewire close:</strong> <code>$this->dispatch('close-dropdown')</code>
            </p>
            <x-ui.dropdown>
                <x-slot:trigger>
                    <x-ui.button variant="secondary">
                        Options <x-icon.chev-d width="14" height="14" />
                    </x-ui.button>
                </x-slot:trigger>
                <a href="#" class="dropdown-item">Edit</a>
                <a href="#" class="dropdown-item">Duplicate</a>
                <a href="#" class="dropdown-item" style="color:var(--bad);">Delete</a>
            </x-ui.dropdown>
        </section>

        {{-- ============================================================ --}}
        {{-- ALERT --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Alert</h2>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <x-ui.alert variant="info">This is an informational message.</x-ui.alert>
                <x-ui.alert variant="success">Operation completed successfully.</x-ui.alert>
                <x-ui.alert variant="warning">Please review before continuing.</x-ui.alert>
                <x-ui.alert variant="danger">Something went wrong. Please try again.</x-ui.alert>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- SPINNER --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Spinner</h2>
            <div style="display:flex;gap:16px;align-items:center;">
                <x-ui.spinner size="sm" />
                <x-ui.spinner />
                <x-ui.spinner size="lg" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- EMPTY STATE --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Empty State</h2>
            <x-ui.card>
                <x-ui.empty-state icon="search" title="No results found" description="Try adjusting your search or filters to find what you need.">
                    <x-slot:action>
                        <x-ui.button variant="secondary">Clear filters</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </x-ui.card>
        </section>

        {{-- ============================================================ --}}
        {{-- TABLE --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Table</h2>
            <x-ui.card :padding="false">
                <x-ui.table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Acme Corp</td>
                            <td><x-ui.pill status="ok">Active</x-ui.pill></td>
                            <td>2026-01-15</td>
                        </tr>
                        <tr>
                            <td>Globex Inc</td>
                            <td><x-ui.pill status="warn">Pending</x-ui.pill></td>
                            <td>2026-02-20</td>
                        </tr>
                        <tr>
                            <td>Initech LLC</td>
                            <td><x-ui.pill status="bad">Inactive</x-ui.pill></td>
                            <td>2026-03-10</td>
                        </tr>
                    </tbody>
                </x-ui.table>
            </x-ui.card>
        </section>

        {{-- ============================================================ --}}
        {{-- ICONS --}}
        {{-- ============================================================ --}}
        <section style="margin-bottom:48px;">
            <h2 class="eyebrow" style="margin-bottom:16px;">Icons (sample)</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:16px;">
                @foreach (['home','user','settings','search','bell','logout','check','close','plus','minus','edit','trash','eye','eye-off','chevron-up','chevron-down','chevron-left','chevron-right','arrow-left','arrow-right','mail','phone','calendar','clock','tag','filter','sort','download','upload','save','copy','link','external-link','lock','unlock','shield','info','warning','error','success','menu','grid','list','dots-vertical','dots-horizontal','refresh'] as $iconName)
                    <div style="text-align:center;">
                        <x-dynamic-component :component="'icon.' . $iconName" width="20" height="20" style="margin:0 auto 6px;" />
                        <span style="font-size:10px;color:var(--ink-5);">{{ $iconName }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>