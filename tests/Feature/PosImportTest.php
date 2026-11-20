<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PosImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_restaurant_cannot_upload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/pos-imports')
            ->assertForbidden()
            ->assertSee('Your account must be assigned to a restaurant');
    }

    public function test_user_can_import_csv_for_their_restaurant(): void
    {
        Storage::fake('local');
        $restaurant = Restaurant::create(['name' => 'Test Restaurant']);
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $csv = UploadedFile::fake()->createWithContent('sales.csv', "order_id,order_date,customer_name,customer_phone,product_name,category,quantity,unit_price,total,payment_method\n1,2026-01-01,,,Coffee,Drinks,2,3.00,6.00,card\n");

        $this->actingAs($user)->post('/pos-imports', ['csv_file' => $csv])
            ->assertRedirect(route('pos-imports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pos_imports', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'rows_successful' => 1,
        ]);
        $this->assertDatabaseHas('orders', ['restaurant_id' => $restaurant->id, 'external_order_id' => '1']);
    }

    public function test_seeded_user_is_assigned_a_restaurant(): void
    {
        $this->seed();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertNotNull($user->restaurant_id);
        $this->assertDatabaseHas('restaurants', ['id' => $user->restaurant_id]);
    }
}
