<?php

namespace Tests\Feature\View;

use Tests\TestCase;

class FormInputComponentTest extends TestCase
{
    public function test_menampilkan_label_dan_pesan_error_per_field(): void
    {
        $view = $this->withViewErrors(['email' => 'Email wajib diisi.'])
            ->blade('<x-form.input name="email" label="Email" type="email" autocomplete="email" dusk="email" />');

        $view->assertSee('<label for="email"', false)
            ->assertSee('Email')
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="email-error"', false)
            ->assertSee('<p id="email-error"', false)
            ->assertSee('Email wajib diisi.')
            ->assertSee('autocomplete="email"', false)
            ->assertSee('dusk="email"', false);
    }

    public function test_tanpa_error_tidak_menandai_field_tidak_valid(): void
    {
        $view = $this->withViewErrors([])
            ->blade('<x-form.input name="name" label="Nama" />');

        $view->assertSee('Nama')
            ->assertDontSee('aria-invalid', false)
            ->assertDontSee('name-error', false);
    }

    public function test_old_input_diisi_ulang_kecuali_kata_sandi(): void
    {
        $session = $this->app['session.store'];
        $session->put('_old_input', ['email' => 'budi@contoh.com', 'password' => 'rahasia123']);
        $this->app['request']->setLaravelSession($session);

        $email = $this->withViewErrors([])->blade('<x-form.input name="email" label="Email" type="email" />');
        $password = $this->withViewErrors([])->blade('<x-form.input name="password" label="Kata sandi" type="password" />');

        $email->assertSee('value="budi@contoh.com"', false);
        $password->assertDontSee('rahasia123')->assertDontSee('value=', false);
    }
}
