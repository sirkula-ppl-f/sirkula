<?php

namespace Tests\Feature\View;

use Tests\TestCase;

class MasterDataComponentTest extends TestCase
{
    private function aksiBaris(bool $active, bool $inUse, ?string $entity = null): string
    {
        return '<x-master-data.row-actions name="Elektronik" dusk-key="elektronik"'
            .' :active="'.($active ? 'true' : 'false').'" :in-use="'.($inUse ? 'true' : 'false').'"'
            .' edit-url="/contoh/ubah" deactivate-url="/contoh/nonaktifkan" activate-url="/contoh/aktifkan" delete-url="/contoh/hapus"'
            .($entity ? ' entity="'.$entity.'"' : '').' />';
    }

    /**
     * Tag pembuka <form> dengan action tertentu.
     */
    private function tagForm(string $html, string $action): string
    {
        $this->assertSame(1, preg_match('/<form\b[^>]*action="'.preg_quote($action, '/').'"[^>]*>/', $html, $cocok), "Form {$action} tidak ditemukan.");

        return $cocok[0];
    }

    public function test_textarea_menampilkan_label_error_dan_nilai(): void
    {
        $view = $this->withViewErrors(['description' => 'Deskripsi terlalu panjang.'])
            ->blade('<x-form.textarea name="description" label="Deskripsi" hint="Opsional." value="<b>lama</b>" dusk="description" />');

        $view->assertSee('<label for="description"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="description-hint description-error"', false)
            ->assertSee('Deskripsi terlalu panjang.')
            ->assertSee('>'.e('<b>lama</b>').'</textarea>', false)
            ->assertSee('dusk="description"', false);
    }

    public function test_textarea_mengisi_ulang_old_input(): void
    {
        $session = $this->app['session.store'];
        $session->put('_old_input', ['description' => 'Isi sebelumnya']);
        $this->app['request']->setLaravelSession($session);

        $this->withViewErrors([])
            ->blade('<x-form.textarea name="description" label="Deskripsi" value="Nilai database" />')
            ->assertSee('>Isi sebelumnya</textarea>', false)
            ->assertDontSee('Nilai database')
            ->assertDontSee('aria-invalid', false);
    }

    public function test_select_menandai_opsi_terpilih_dan_placeholder(): void
    {
        $view = $this->withViewErrors([])->blade(
            '<x-form.select name="status" label="Status" placeholder="Semua" :options="$options" value="nonaktif" />',
            ['options' => ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']],
        );

        $view->assertSee('<label for="status"', false)
            ->assertSee('<option value="">Semua</option>', false)
            ->assertSee('<option value="aktif"', false)
            ->assertDontSee('<option value="aktif" selected', false)
            ->assertSee('<option value="nonaktif" selected>Nonaktif</option>', false);
    }

    public function test_select_mengabaikan_nilai_bukan_skalar_dan_menampilkan_error(): void
    {
        $view = $this->withViewErrors(['status' => 'Status tidak valid.'])->blade(
            '<x-form.select name="status" label="Status" :options="$options" :value="$value" />',
            ['options' => ['aktif' => 'Aktif'], 'value' => ['aktif']],
        );

        $view->assertDontSee('selected', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="status-error"', false)
            ->assertSee('Status tidak valid.');
    }

    public function test_status_badge_menuliskan_status_sebagai_teks(): void
    {
        $this->blade('<x-status-badge :active="true" />')->assertSeeText('Aktif')->assertDontSeeText('Nonaktif');
        $this->blade('<x-status-badge :active="false" />')->assertSeeText('Nonaktif');
    }

    public function test_aksi_baris_data_aktif_yang_belum_dipakai(): void
    {
        $view = $this->blade($this->aksiBaris(active: true, inUse: false));

        $view->assertSee('href="/contoh/ubah"', false)
            ->assertSee('action="/contoh/nonaktifkan"', false)
            ->assertSee('action="/contoh/hapus"', false)
            ->assertDontSee('/contoh/aktifkan', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('data-submit-once', false)
            ->assertSee('dusk="edit-elektronik"', false)
            ->assertSee('dusk="deactivate-elektronik"', false)
            ->assertSee('dusk="delete-elektronik"', false)
            ->assertSee('<span class="sr-only"> Elektronik</span>', false);
    }

    public function test_aksi_baris_data_nonaktif_menampilkan_tombol_aktifkan(): void
    {
        $view = $this->blade($this->aksiBaris(active: false, inUse: false));

        $view->assertSee('action="/contoh/aktifkan"', false)
            ->assertSee('dusk="activate-elektronik"', false)
            ->assertDontSee('dusk="deactivate-elektronik"', false);
    }

    public function test_tombol_hapus_tidak_tampil_bila_data_sudah_dipakai(): void
    {
        $view = $this->blade($this->aksiBaris(active: true, inUse: true));

        $view->assertDontSee('/contoh/hapus', false)
            ->assertDontSee('dusk="delete-elektronik"', false)
            ->assertSee('dusk="deactivate-elektronik"', false);
    }

    public function test_aksi_baris_tanpa_entity_memakai_teks_konfirmasi_umum(): void
    {
        $html = (string) $this->blade($this->aksiBaris(active: true, inUse: false));

        $nonaktifkan = $this->tagForm($html, '/contoh/nonaktifkan');
        $hapus = $this->tagForm($html, '/contoh/hapus');

        $this->assertStringContainsString('data-submit-once', $nonaktifkan);
        $this->assertStringContainsString('data-confirm="'.e(__('master-data.confirm.deactivate', ['name' => 'Elektronik'])).'"', $nonaktifkan);
        $this->assertStringContainsString('data-confirm-title="Nonaktifkan data?"', $nonaktifkan);
        $this->assertStringContainsString('data-confirm-label="Nonaktifkan"', $nonaktifkan);
        $this->assertStringNotContainsString('data-confirm-variant', $nonaktifkan);

        $this->assertStringContainsString('data-submit-once', $hapus);
        $this->assertStringContainsString('data-confirm="Hapus “Elektronik”? Tindakan ini tidak bisa dibatalkan."', $hapus);
        $this->assertStringContainsString('data-confirm-title="Hapus data?"', $hapus);
        $this->assertStringContainsString('data-confirm-label="Hapus"', $hapus);
        $this->assertStringContainsString('data-confirm-variant="danger"', $hapus);
    }

    public function test_aksi_baris_dengan_entity_memakai_teks_konfirmasi_khusus(): void
    {
        $html = (string) $this->blade($this->aksiBaris(active: true, inUse: false, entity: 'categories'));

        $this->assertStringContainsString(
            'data-confirm="Nonaktifkan kategori “Elektronik”? Kategori nonaktif tidak bisa dipilih untuk listing baru, tetapi listing lama tetap tersimpan."',
            $this->tagForm($html, '/contoh/nonaktifkan'),
        );
        $this->assertStringContainsString('data-confirm-title="Hapus kategori?"', $this->tagForm($html, '/contoh/hapus'));
        // Label tombol tidak punya teks khusus, jadi memakai teks umum.
        $this->assertStringContainsString('data-confirm-label="Hapus"', $this->tagForm($html, '/contoh/hapus'));

        $tidakDikenal = (string) $this->blade($this->aksiBaris(active: true, inUse: false, entity: 'tidak-ada'));

        $this->assertStringContainsString('data-confirm-title="Nonaktifkan data?"', $this->tagForm($tidakDikenal, '/contoh/nonaktifkan'));
    }

    public function test_aksi_aktifkan_tidak_memakai_konfirmasi(): void
    {
        $this->blade($this->aksiBaris(active: false, inUse: true, entity: 'materials'))
            ->assertSee('action="/contoh/aktifkan"', false)
            ->assertDontSee('data-confirm', false);
    }

    public function test_dialog_konfirmasi_aksesibel_dan_punya_atribut_dusk(): void
    {
        $view = $this->blade('<x-confirm-dialog />');

        $view->assertSee('<dialog', false)
            ->assertSee('data-confirm-dialog', false)
            ->assertSee('aria-labelledby="confirm-dialog-title"', false)
            ->assertSee('id="confirm-dialog-title"', false)
            ->assertSee('aria-describedby="confirm-dialog-message"', false)
            ->assertSee('id="confirm-dialog-message"', false)
            ->assertSee('<form method="dialog"', false)
            ->assertSee('value="cancel"', false)
            ->assertSee('autofocus', false)
            ->assertSee('value="confirm"', false)
            ->assertSee('dusk="confirm-dialog"', false)
            ->assertSee('dusk="confirm-dialog-cancel"', false)
            ->assertSee('dusk="confirm-dialog-confirm"', false)
            ->assertSeeInOrder(['Konfirmasi tindakan', 'Batal', 'Lanjutkan']);
    }
}
