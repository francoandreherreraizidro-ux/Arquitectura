<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Province;
use App\Models\Region;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncLocationsCommandTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://escale.minedu.gob.pe/padron/rest';

    public function test_sincroniza_regiones_provincias_distritos_y_colegios(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            self::BASE.'/regiones' => Http::response([
                'region' => [
                    ['idRegion' => '15', 'nombreRegion' => 'LIMA', 'pointX' => -12.04, 'pointY' => -77.04, 'zoom' => 10],
                ],
            ]),
            // La API devuelve un objeto (no lista) cuando hay una sola provincia.
            self::BASE.'/provincias*' => Http::response([
                'provincia' => ['idProvincia' => '1501', 'nombreProvincia' => 'LIMA', 'pointX' => -12.04, 'pointY' => -77.04],
            ]),
            self::BASE.'/distritos*' => Http::response([
                'distrito' => [
                    ['idDistrito' => '150101', 'nombreDistrito' => 'LIMA'],
                    ['idDistrito' => '150102', 'nombreDistrito' => 'ANCON'],
                ],
            ]),
            self::BASE.'/instituciones*' => function ($request) {
                if ($request['ubigeo'] === '150101') {
                    return Http::response(['items' => [
                        ['codMod' => '0273572', 'cenEdu' => 'JUAN JIMENEZ PIMENTEL', 'cenPob' => 'LIMA', 'dirCen' => 'JR. LAMPA 240', 'director' => 'PEDRO PEREZ'],
                        ['codMod' => '0273573', 'cenEdu' => 'OTRO', 'cenPob' => 'LIMA', 'dirCen' => 'AV. X'],
                    ]]);
                }

                // Un solo colegio: la API devuelve un objeto en vez de lista.
                return Http::response(['items' => ['codMod' => '0300001', 'cenEdu' => 'COLEGIO ANCON']]);
            },
        ]);

        $this->artisan('sync:locations')
            ->expectsOutput('Sincronización completa.')
            ->assertExitCode(0);

        $this->assertSame(1, Region::count());
        $this->assertSame(1, Province::count());
        $this->assertSame(2, District::count());
        $this->assertSame(3, School::count());

        $this->assertSame('150101', School::find('0273572')->idDistrito);
        $this->assertSame('PEDRO PEREZ', School::find('0273572')->director);
        $this->assertSame('150102', School::find('0300001')->idDistrito);

        Http::assertSent(fn ($request) => $request->hasHeader('Accept', 'application/json'));
    }

    public function test_reporta_error_si_la_api_de_regiones_falla(): void
    {
        Http::fake([self::BASE.'/regiones' => Http::response(null, 500)]);

        $this->artisan('sync:locations')
            ->expectsOutput('Error al obtener regiones')
            ->assertExitCode(0);

        $this->assertSame(0, Region::count());
    }
}
