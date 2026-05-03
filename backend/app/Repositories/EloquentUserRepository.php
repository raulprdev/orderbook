<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Entities\User as DomainUser;
use App\Domain\ValueObjects\Money;
use App\Models\User as EloquentUser;
use App\Repositories\Contracts\UserRepository;

final class EloquentUserRepository implements UserRepository
{
    public function save(DomainUser $user): DomainUser
    {
        $eloquent = EloquentUser::findOrFail($user->id());

        $this->applyToEloquent($user, $eloquent);
        $eloquent->save();

        return $this->toDomain($eloquent);
    }

    public function findById(int $id): ?DomainUser
    {
        return $this->toDomain(EloquentUser::find($id));
    }

    public function findByIdForUpdate(int $id): ?DomainUser
    {
        return $this->toDomain(
            EloquentUser::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->first()
        );
    }

    private function toDomain(?EloquentUser $eloquent): ?DomainUser
    {
        if ($eloquent === null) {
            return null;
        }

        return new DomainUser(
            id: $eloquent->id,
            balance: Money::fromMicroUsd($eloquent->balance),
        );
    }

    private function applyToEloquent(DomainUser $user, EloquentUser $eloquent): void
    {
        $eloquent->balance = $user->balance()->microUsd();
    }
}