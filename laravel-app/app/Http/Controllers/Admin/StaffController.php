<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ClampsPaginationPage;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller{
    use ClampsPaginationPage;

    // Display a paginated, searchable listing of all staff accounts.
    public function index(Request $request){
        $staff = $this->paginateClamped(function () use ($request) {
            $query = User::query();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'ILIKE', '%' . $request->input('search') . '%')
                        ->orWhere('email', 'ILIKE', '%' . $request->input('search') . '%');
                });
            }

            if ($request->filled('role')) {
                $query->where('role', $request->input('role'));
            }

            return $query->orderByDesc('user_id');
        }, $request, 8);

        // Full, unpaginated list of staff names for the search box's
        // suggestions dropdown, deliberately separate from $staff (only the
        // current page of up to 8 results), since suggestions need to cover
        // every account, not just whichever page happens to be showing.
        $allStaffNames = User::query()->orderBy('name')->pluck('name');

        // AJAX refresh: after the modal creates/updates/deletes an account, or when
        // the admin clicks a Prev/Next pagination link, the frontend re-requests
        // this same endpoint asking for JSON instead of a full page. This returns
        // just the table + pagination fragment (see admin.staff._table) so the page
        // stays in sync with the real, server-computed pagination state without a
        // manual browser reload, same pattern as Menu Management and Inventory.
    
        // staffSuggestionsHtml rides along in the same response for the same reason
        // the Category filter's options do on Menu Management: without it, an
        // account just added/renamed/deleted wouldn't show up in the search
        // suggestions until a real page reload.
        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('admin.staff._table', [
                    'staff' => $staff,
                ])->render(),
                'staffSuggestionsHtml' => view('admin.partials._search_suggestions', [
                    'suggestions' => $allStaffNames,
                ])->render(),
            ]);
        }

        return view('admin.staff.index', [
            'staff' => $staff,
            'allStaffNames' => $allStaffNames,
        ]);
    }

    // Kept for direct-link / no-JS access
    // Add Staff now opens as a popup on the index page instead.
    public function create(){
        return view('admin.staff.create');
    }

    // A real email, not just "contains an @". `email:rfc,filter` runs both
    // Laravel's RFC 5322 grammar check and PHP's filter_var(FILTER_VALIDATE_EMAIL)
    // pass, catching malformed addresses (double dots, bad domain syntax, etc.)
    // that a bare `email` rule alone would still accept. DNS/MX lookups are
    // deliberately not used (`email:rfc,dns`) since the system is designed to run
    // entirely on the restaurant's local network without guaranteed outbound
    // internet access, a DNS check would make account creation fail
    // offline for otherwise valid addresses.
    private function emailRules(): array{
        return ['required', 'string', 'email:rfc,filter', 'max:150'];
    }

    // Required text that must not contain any digits (0-9), same rule
    // MenuController applies to item_name/category, used here for the staff
    // full-name field.
    private function textNoDigitsRule(): array{
        return ['required', 'string', 'max:100', 'regex:/^[^0-9]+$/u'];
    }

    // A staff account can't be created with a weak password: at least 10
    // characters, upper + lower case, at least one number, and at least one
    // symbol. `uncompromised()` (a live Have I Been Pwned lookup) is deliberately
    // left off for the same offline-first reason as the email rule above, it
    // requires an outbound API call this system cannot depend on.
    private function passwordRules(bool $required): array{
        $rule = Password::min(10)->mixedCase()->numbers()->symbols();

        return $required
            ? ['required', 'confirmed', $rule]
            : ['nullable', 'confirmed', $rule];
    }

    private function validationMessages(): array{
        return [
            'email.email' => 'Please enter a valid email address.',
            'password.confirmed' => 'Password confirmation does not match.',
            'name.regex' => 'Full name must not contain any numbers.',
        ];
    }

    // Rejects an email that already exists (case-insensitive, whitespace-trimmed)
    // so "Owner@DesiZaika.com" and "owner@desizaika.com " can't both be registered
    // as if they were different accounts. $ignoreId excludes the record currently
    // being edited from the check. Mirrors duplicateNameRule() in
    // MenuController/InventoryController.
    private function duplicateEmailRule(?int $ignoreId = null): \Closure{
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
            $exists = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($value))])
                ->when($ignoreId, fn ($query) => $query->where('user_id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $fail('This email is already registered to another staff account.');
            }
        };
    }

    // Postgres unique_violation is SQLSTATE 23505. Last line of defense against a
    // race between two simultaneous submissions for the same email, the
    // application-level duplicateEmailRule() above should already catch this, but
    // the DB-level unique index (see the accompanying migration) guarantees a
    // duplicate can never actually be written, even if that check is ever bypassed.
    private function isDuplicateEmailViolation(QueryException $e): bool{
        return $e->getCode() === '23505'
            && str_contains($e->getMessage(), 'users_email_unique');
    }

    private function duplicateEmailResponse(Request $request){
        $message = 'This email is already registered to another staff account.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['email' => [$message]],
            ], 422);
        }

        return redirect()->back()->withInput()->withErrors(['email' => $message]);
    }

    // Decides redirect-vs-422 itself via $request->wantsJson(), rather than
    // letting Laravel's automatic ValidationException handling decide via
    // expectsJson(), the same fix already applied in MenuController and
    // InventoryController after that path was found to silently redirect AJAX
    // validation failures instead of returning 422 JSON.
    private function validationFailedResponse(Request $request, $validator){
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Please check the form and try again.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Store a newly created staff account in the database.
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,cashier,kitchen',
            'name' => $this->textNoDigitsRule(),
            'email' => [...$this->emailRules(), $this->duplicateEmailRule()],
            'password' => $this->passwordRules(required: true),
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['name'] = trim($validated['name']);
        $validated['email'] = strtolower(trim($validated['email']));
        unset($validated['password_confirmation']);

        try {
            $user = User::create($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateEmailViolation($e)) {
                return $this->duplicateEmailResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'staff' => $user,
                'message' => 'Staff account created successfully.',
            ]);
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff account created successfully.');
    }

    // Show the form for editing an existing staff account.
    public function edit(User $user){
        return view('admin.staff.edit', [
            'staffMember' => $user,
        ]);
    }

    // Update an existing staff account in the database. Password is optional here,
    // left blank, the existing password is preserved; filled in, it's held to
    // the exact same strength policy as account creation.
    public function update(Request $request, User $user){
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,cashier,kitchen',
            'name' => $this->textNoDigitsRule(),
            'email' => [...$this->emailRules(), $this->duplicateEmailRule($user->user_id)],
            'password' => $this->passwordRules(required: false),
        ], $this->validationMessages());

        if ($validator->fails()) {
            return $this->validationFailedResponse($request, $validator);
        }

        $validated = $validator->validated();
        $validated['name'] = trim($validated['name']);
        $validated['email'] = strtolower(trim($validated['email']));
        unset($validated['password_confirmation']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        try {
            $user->update($validated);
        } catch (QueryException $e) {
            if ($this->isDuplicateEmailViolation($e)) {
                return $this->duplicateEmailResponse($request);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'staff' => $user->fresh(),
                'message' => 'Staff account updated successfully.',
            ]);
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff account updated successfully.');
    }

    // Remove a staff account from the database. The database's foreign keys
    // (orders.cashier_id, stock_movements.user_id) deliberately reject this if the
    // account has any order or stock-movement history tied to it, protecting the
    // audit trail, so rather than let that surface as a raw 500 error,
    // it's caught here and turned into a clear, specific message the admin can act
    // on. Same pattern as InventoryController::destroy().
    public function destroy(Request $request, User $user){
        try {
            $user->delete();
        } catch (QueryException $e) {
            $message = "Can't delete \"{$user->name}\", they have order or stock movement history tied to their account.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()->route('admin.staff.index')->with('error', $message);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Staff account removed.',
            ]);
        }

        return redirect()
            ->route('admin.staff.index')
            ->with('success', 'Staff account removed.');
    }
}