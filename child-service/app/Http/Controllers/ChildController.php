<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChildController extends Controller
{
    public function index(Request $request)
    {
        return Child::query()
            ->where('parent_id', $this->parentId($request))
            ->orderBy('first_name')
            ->get();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'subscription_id' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:1', 'max:18'],
            'birthdate' => ['nullable', 'date'],
            'skill_level' => ['nullable', 'string', 'max:50'],
        ]);

        $pin = $this->generatePin();

        $child = Child::query()->create([
            ...$data,
            'parent_id' => $this->parentId($request),
            'username' => $this->generateUsername($data['first_name'], $data['last_name'] ?? null),
            'password' => $pin,
            'status' => 'active',
            'credentials_rotated_at' => now(),
        ]);

        return response()->json([
            'child' => $child,
            'credentials' => [
                'username' => $child->username,
                'pin' => $pin,
            ],
        ], 201);
    }

    public function show(Request $request, Child $child)
    {
        $this->abortUnlessOwnedByParent($request, $child);

        return $child;
    }

    public function update(Request $request, Child $child)
    {
        $this->abortUnlessOwnedByParent($request, $child);

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'subscription_id' => ['nullable', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:1', 'max:18'],
            'birthdate' => ['nullable', 'date'],
            'skill_level' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);

        $child->update($data);

        return $child->refresh();
    }

    public function destroy(Request $request, Child $child): JsonResponse
    {
        $this->abortUnlessOwnedByParent($request, $child);

        $child->delete();

        return response()->json(null, 204);
    }

    public function resetCredentials(Request $request, Child $child): JsonResponse
    {
        $this->abortUnlessOwnedByParent($request, $child);

        $pin = $this->generatePin();

        $child->update([
            'password' => $pin,
            'credentials_rotated_at' => now(),
        ]);

        return response()->json([
            'child' => $child->refresh(),
            'credentials' => [
                'username' => $child->username,
                'pin' => $pin,
            ],
        ]);
    }

    private function parentId(Request $request): string
    {
        return (string) $request->attributes->get('parent_id');
    }

    private function abortUnlessOwnedByParent(Request $request, Child $child): void
    {
        abort_unless($child->parent_id === $this->parentId($request), 404);
    }

    private function generateUsername(string $firstName, ?string $lastName): string
    {
        $base = Str::slug(trim($firstName.' '.($lastName ?? '')), '_') ?: 'reader';
        $base = Str::limit($base, 24, '');

        do {
            $username = $base.'_'.Str::lower(Str::random(5));
        } while (Child::query()->where('username', $username)->exists());

        return $username;
    }

    private function generatePin(): string
    {
        $length = max(4, (int) config('child_service.pin_length', 6));
        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }
}
