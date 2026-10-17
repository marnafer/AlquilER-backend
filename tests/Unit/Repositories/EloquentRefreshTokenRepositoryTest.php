<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentRefreshTokenRepository;

final class EloquentRefreshTokenRepositoryTest extends RepositorioTestCase
{
    private EloquentRefreshTokenRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentRefreshTokenRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('refresh_tokens', [
            ['id' => 1, 'usuario_id' => 1, 'token' => hash('sha256', 't1'), 'expires_at' => '2099-01-01 00:00:00'],
            ['id' => 2, 'usuario_id' => 1, 'token' => hash('sha256', 't2'), 'expires_at' => '2000-01-01 00:00:00'],
            ['id' => 3, 'usuario_id' => 2, 'token' => hash('sha256', 't3'), 'expires_at' => '2099-01-01 00:00:00'],
        ]);
    }

    public function test_create_y_find_valid(): void
    {
        $token = $this->repo->create([
            'usuario_id' => 1,
            'token' => hash('sha256', 's3cr3t'),
            'expires_at' => '2099-01-01 00:00:00',
        ]);

        $this->assertNotNull($token->id);
        $this->assertNotNull($this->repo->findValidByToken('s3cr3t'));
    }

    public function test_find_valid_by_token_descarta_vencidos_y_desconocidos(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findValidByToken('t1'));
        $this->assertNull($this->repo->findValidByToken('t2'));
        $this->assertNull($this->repo->findValidByToken('nope'));
    }

    public function test_delete_by_token(): void
    {
        $this->sembrar();

        $this->repo->deleteByToken('t1');

        $this->assertNull($this->repo->findValidByToken('t1'));
        $this->assertSame(
            0,
            (int) $this->capsule
                ->table('refresh_tokens')
                ->where('id', 1)
                ->count()
        );
    }

    public function test_delete_by_id(): void
    {
        $this->sembrar();

        $this->repo->deleteById(3);

        $this->assertFalse(
            $this->capsule->table('refresh_tokens')->where('id', 3)->exists()
        );
    }

    public function test_delete_by_usuario_id_devuelve_cantidad(): void
    {
        $this->sembrar();

        $this->assertSame(2, $this->repo->deleteByUsuarioId(1));
        $this->assertSame(0, $this->repo->deleteByUsuarioId(1));
        $this->assertTrue(
            $this->capsule->table('refresh_tokens')->where('id', 3)->exists()
        );
    }
}
