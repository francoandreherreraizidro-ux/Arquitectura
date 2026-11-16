<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Province;
use App\Models\Region;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerSqliteLeftFunction();

        Region::create(['idRegion' => '15', 'nombreRegion' => 'LIMA', 'pointX' => -12.046374, 'pointY' => -77.042793, 'zoom' => 10]);
        Region::create(['idRegion' => '04', 'nombreRegion' => 'AREQUIPA']);

        Province::create(['idProvincia' => '1501', 'nombreProvincia' => 'LIMA']);
        Province::create(['idProvincia' => '1502', 'nombreProvincia' => 'BARRANCA']);
        Province::create(['idProvincia' => '0401', 'nombreProvincia' => 'AREQUIPA']);

        District::create(['idDistrito' => '150101', 'nombreDistrito' => 'LIMA']);
        District::create(['idDistrito' => '150102', 'nombreDistrito' => 'ANCON']);
        District::create(['idDistrito' => '150201', 'nombreDistrito' => 'BARRANCA']);

        School::create(['codMod' => '0273572', 'idDistrito' => '150101', 'nombreCenEdu' => 'JUAN JIMENEZ PIMENTEL', 'cenPob' => 'LIMA', 'dirCen' => 'JR. LAMPA 240']);
        School::create(['codMod' => '0999999', 'idDistrito' => '150102', 'nombreCenEdu' => 'OTRO COLEGIO']);
    }

    public function test_lista_todas_las_regiones(): void
    {
        $this->getJson('/api/regiones')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['idRegion' => '15', 'nombreRegion' => 'LIMA']);
    }

    public function test_lista_provincias_de_una_region(): void
    {
        $this->getJson('/api/provincias/15')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['idProvincia' => '1501'])
            ->assertJsonFragment(['idProvincia' => '1502'])
            ->assertJsonMissing(['idProvincia' => '0401']);
    }

    public function test_region_sin_provincias_devuelve_404(): void
    {
        $this->getJson('/api/provincias/99')
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    public function test_lista_distritos_de_una_provincia(): void
    {
        $this->getJson('/api/distritos/1501')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['idDistrito' => '150201']);
    }

    public function test_provincia_sin_distritos_devuelve_404(): void
    {
        $this->getJson('/api/distritos/9999')->assertNotFound();
    }

    public function test_lista_colegios_de_un_distrito(): void
    {
        $this->getJson('/api/colegios/150101')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codMod', '0273572')
            ->assertJsonPath('data.0.nombreCenEdu', 'JUAN JIMENEZ PIMENTEL');
    }

    public function test_distrito_sin_colegios_devuelve_404(): void
    {
        $this->getJson('/api/colegios/150201')->assertNotFound();
    }

    public function test_la_api_permite_cors(): void
    {
        $this->getJson('/api/regiones', ['Origin' => 'https://cliente.example'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
