<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentPasswordResetRepository;

final class EloquentPasswordResetRepositoryTest extends RepositorioTestCase
{
    private EloquentPasswordResetRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentPasswordResetRepository();
    }

    public function test_create_devuelve_el_id(): void
    {
        $id = $this->repo->create([
            'email' => 'user@x.test',
            'token' => hash('sha256', 'plain'),
            'expiracion' => '2099-01-01 00:00:00',
            'usado' => 0,
        ]);

        $this->assertIsInt($id);
        $this->assertTrue(
            $this->capsule->table('password_resets')->where('id', $id)->exists()
        );
    }

    public function test_find_by_token_and_email_hashea_el_token(): void
    {
        $this->insertar('password_resets', [
            [
                'id' => 1,
                'email' => 'user@x.test',
                'token' => hash('sha256', 'plano'),
                'expiracion' => '2099-01-01 00:00:00',
                'usado' => 0,
            ],
        ]);

        $encontrado = $this->repo->findByTokenAndEmail('plano', 'user@x.test');

        $this->assertNotNull($encontrado);
        $this->assertSame('user@x.test', $encontrado->email);

        $this->assertNull($this->repo->findByTokenAndEmail('otro', 'user@x.test'));
        $this->assertNull($this->repo->findByTokenAndEmail('plano', 'nadie@x.test'));
    }

    public function test_marcar_usado(): void
    {
        $this->insertar('password_resets', [
            ['id' => 1, 'email' => 'user@x.test', 'token' => 'abc', 'usado' => 0],
        ]);

        $this->assertTrue($this->repo->marcarUsado(1));
        $this->assertSame(
            1,
            (int) $this->capsule
                ->table('password_resets')
                ->where('id', 1)
                ->value('usado')
        );
        $this->assertFalse($this->repo->marcarUsado(99));
    }
}
