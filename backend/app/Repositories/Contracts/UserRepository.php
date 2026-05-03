<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Entities\User;

interface UserRepository
{
    public function save(User $user): User;

    public function findById(int $id): ?User;

    public function findByIdForUpdate(int $id): ?User;
}