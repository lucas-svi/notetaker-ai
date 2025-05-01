<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

final class UserApiTest extends TestCase
{
    private Client $http;

    protected function setUp(): void
    {
        $this->http = new Client([
            'base_uri'    => getenv('API_BASE_URL') . 'index.php/',
            'http_errors' => false,
            'timeout'     => 5,
        ]);
    }

    /* -----------------------------------------------------------------------
     *  GET  /user/list
     * --------------------------------------------------------------------- */
    public function testGet_UserList(): void
    {
        $r = $this->http->get('user/list');
        $this->assertSame(200, $r->getStatusCode(), 'Expected 200 OK from /user/list');
    }

    /* -----------------------------------------------------------------------
     *  POST /user/create
     * --------------------------------------------------------------------- */
    public function testPost_CreateUser(): array
    {
        $username = 'testuser_' . uniqid();
        $email    = $username . '@example.com';
        $password = 'UltraSecurePassword69420';

        $r = $this->http->post('user/create', [
            'form_params' => [
                'username'         => $username,
                'email'            => $email,
                'password'         => $password,
                'confirm_password' => $password,
            ],
        ]);

        $this->assertSame(
            201,
            $r->getStatusCode(),
            'Expected 201 CREATED from /user/create'
        );

        return ['username' => $username, 'password' => $password];
    }

    /* -----------------------------------------------------------------------
     *  POST /user/login  (happy path)
     * --------------------------------------------------------------------- */
    /**
     * @depends testPost_CreateUser
     */
    public function testPost_LoginUser(array $creds): void
    {
        $r = $this->http->post('user/login', [
            'form_params' => [
                'username' => $creds['username'],
                'password' => $creds['password'],
            ],
        ]);

        $this->assertSame(
            200,
            $r->getStatusCode(),
            'Expected 200 OK when logging in with valid credentials'
        );
    }

    /* -----------------------------------------------------------------------
     *  POST /user/login  (negative path)
     * --------------------------------------------------------------------- */
    public function testPost_FailedLogin(): void
    {
        $r = $this->http->post('user/login', [
            'form_params' => [
                'username' => 'nosuchuser',
                'password' => 'totallyWrong',
            ],
        ]);

        $this->assertSame(
            401,
            $r->getStatusCode(),
            'Expected 401 Unauthorized on bad login'
        );
    }
}
