<?php

namespace Tests\Feature;

use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProdutoTest extends TestCase
{
    use RefreshDatabase; // Faltou essa trait na aula -> serve pra utilizar e dar refresh na memória temporária do sqlite


    public function test_cria_produto_com_sucesso(): void
    {
        $this->postJson('/api/produtos', [
            'nome' => 'Lajota', 
            'preco_custo' => 100,
            'preco' => 110,
        ])->assertCreated();

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Lajota']);
    }

    private function criarProdutos(): void
    {
        Produto::create(['nome' => 'Barato', 'preco_custo' => 5, 'preco' => 10]);
        Produto::create(['nome' => 'Medio', 'preco_custo' => 40, 'preco' => 50]);
        Produto::create(['nome' => 'Caro', 'preco_custo' => 80, 'preco' => 100]);
    }

    public function test_lista_produtos_sem_filtros(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_filtra_produtos_por_preco_minimo(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?preco_min=50')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_produtos_por_preco_maximo(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?preco_max=50')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filtra_produtos_por_faixa_de_preco(): void
    {
        $this->criarProdutos();

        $this->getJson('/api/produtos?preco_min=20&preco_max=60')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_rejeita_preco_max_menor_que_preco_min(): void
    {
        $this->getJson('/api/produtos?preco_min=60&preco_max=20')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preco_max');
    }

    public function test_atualiza_produto_com_sucesso(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->putJson("/api/produtos/{$produto->id}", ['nome' => 'Tijolo'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Tijolo');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'nome' => 'Tijolo']);
    }

    public function test_rejeita_atualizacao_com_margem_menor_que_10_por_cento(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->putJson("/api/produtos/{$produto->id}", ['preco' => 105])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preco');

        $this->assertDatabaseHas('produtos', ['id' => $produto->id, 'preco' => 110]);
    }

    public function test_remove_produto_com_sucesso(): void
    {
        $produto = Produto::create(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        $this->deleteJson("/api/produtos/{$produto->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }
}
