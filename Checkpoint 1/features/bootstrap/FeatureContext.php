<?php

use App\Models\Product;
use App\Models\User;
use Behat\Behat\Context\Context;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class FeatureContext implements Context
{
    private object $laravel;
    private ?TestResponse $response = null;

    public function __construct()
    {
        $this->configureTestingEnvironment();
    }

    #[BeforeScenario]
    public function bootLaravel(): void
    {
        $this->laravel = new class ('behat') extends TestCase {
            public function setUpBdd(): void
            {
                parent::setUp();
                $this->artisan('migrate:fresh');
            }
        };

        $this->laravel->setUpBdd();
    }

    #[AfterScenario]
    public function closeLaravel(): void
    {
        $this->response = null;
    }

    /**
     * @Given que nao existe usuario com e-mail :email
     */
    public function queNaoExisteUsuarioComEmail(string $email): void
    {
        Assert::assertFalse(
            User::query()->where('email', strtolower($email))->exists(),
            "O usuario {$email} ja existe antes do cenario."
        );
    }

    /**
     * @Given que existe usuario :name com e-mail :email
     */
    public function queExisteUsuarioComEmail(string $name, string $email): void
    {
        User::factory()->create([
            'name' => $name,
            'email' => strtolower($email),
            'password' => 'password123',
            'role' => 'user',
        ]);
    }

    /**
     * @When eu cadastro o usuario :name com e-mail :email e senha :password
     */
    public function euCadastroOUsuarioComEmailESenha(string $name, string $email, string $password): void
    {
        $this->response = $this->laravel->from('/register')->post('/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    /**
     * @When eu tento cadastrar o usuario :name com e-mail :email, senha :password e confirmacao :confirmation
     */
    public function euTentoCadastrarOUsuarioComEmailSenhaEConfirmacao(
        string $name,
        string $email,
        string $password,
        string $confirmation
    ): void {
        $this->response = $this->laravel->from('/register')->post('/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ]);
    }

    /**
     * @When cadastro o produto :name com SKU :sku, preco :price e estoque :stock
     */
    public function cadastroOProdutoComSkuPrecoEEstoque(string $name, string $sku, float $price, int $stock): void
    {
        $this->response = $this->laravel->post('/products', [
            'name' => $name,
            'description' => 'Produto cadastrado no fluxo BDD.',
            'price' => $price,
            'sku' => $sku,
            'stock' => $stock,
            'status' => 'active',
        ]);
    }

    /**
     * @Then o cadastro deve ser concluido com sucesso
     */
    public function oCadastroDeveSerConcluidoComSucesso(): void
    {
        Assert::assertInstanceOf(TestResponse::class, $this->response);

        $this->response->assertRedirect(route('products.index'));
        $this->response->assertSessionHas('success', 'Cadastro realizado com sucesso.');
    }

    /**
     * @Then o cadastro deve ser recusado no campo :field
     */
    public function oCadastroDeveSerRecusadoNoCampo(string $field): void
    {
        Assert::assertInstanceOf(TestResponse::class, $this->response);

        $this->response->assertRedirect('/register');
        $this->response->assertSessionHasErrors([$field]);
    }

    /**
     * @Then o usuario :email deve existir com perfil :role
     */
    public function oUsuarioDeveExistirComPerfil(string $email, string $role): void
    {
        Assert::assertTrue(
            User::query()
                ->where('email', strtolower($email))
                ->where('role', $role)
                ->exists(),
            "O usuario {$email} nao foi cadastrado com perfil {$role}."
        );
    }

    /**
     * @Then deve existir apenas :quantity usuario com e-mail :email
     */
    public function deveExistirApenasUsuarioComEmail(int $quantity, string $email): void
    {
        Assert::assertSame(
            $quantity,
            User::query()->where('email', strtolower($email))->count()
        );
    }

    /**
     * @Then o usuario :email nao deve existir
     */
    public function oUsuarioNaoDeveExistir(string $email): void
    {
        Assert::assertFalse(
            User::query()->where('email', strtolower($email))->exists(),
            "O usuario {$email} foi cadastrado indevidamente."
        );
    }

    /**
     * @Then o produto :name deve existir no catalogo com SKU :sku
     */
    public function oProdutoDeveExistirNoCatalogoComSku(string $name, string $sku): void
    {
        Assert::assertInstanceOf(TestResponse::class, $this->response);

        $this->response->assertRedirect();
        $this->response->assertSessionHas('success', 'Produto criado com sucesso.');

        Assert::assertTrue(
            Product::query()
                ->where('name', $name)
                ->where('sku', strtoupper($sku))
                ->exists(),
            "O produto {$name} com SKU {$sku} nao foi encontrado no catalogo."
        );
    }

    /**
     * @Then devo estar autenticado como :email
     */
    public function devoEstarAutenticadoComo(string $email): void
    {
        Assert::assertTrue(Auth::check(), 'O usuario nao esta autenticado.');
        Assert::assertSame(strtolower($email), Auth::user()?->email);
    }

    private function configureTestingEnvironment(): void
    {
        $variables = [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'MAIL_MAILER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
        ];

        foreach ($variables as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
