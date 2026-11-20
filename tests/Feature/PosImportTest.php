<?php

namespace Tests\Feature;

use App\Models\PosImportError;
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

    public function test_import_interprets_slash_dates_as_day_first_and_rejects_invalid_dates(): void
    {
        Storage::fake('local');
        $restaurant = Restaurant::create(['name' => 'Test Restaurant']);
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $csv = UploadedFile::fake()->createWithContent('sales.csv', <<<'CSV'
order_id,order_date,customer_name,customer_phone,product_name,category,quantity,unit_price,total,payment_method
1,21/11/2026  12:45:00,Jane,123,Coffee,Drinks,1,3.00,3.00,card
2,21/11/2026 12:45,,,Coffee,Drinks,1,3.00,3.00,card
3,03/04/2026 09:30,,,Coffee,Drinks,1,3.00,3.00,card
4,31/02/2026 12:45,,,Coffee,Drinks,1,3.00,3.00,card
5,2026-01-02 08:15:00,,,Coffee,Drinks,1,3.00,3.00,card
6,02/30/2026 12:45,,,Coffee,Drinks,1,3.00,3.00,card
7,3/4/2026,,,Coffee,Drinks,1,3.00,3.00,card
8,21/11/2026 25:00,,,Coffee,Drinks,1,3.00,3.00,card
9,21/11/2026 12:45,,,Coffee,Drinks,1,3.00,4.00,card
CSV);

        $this->actingAs($user)->post('/pos-imports', ['csv_file' => $csv])
            ->assertRedirect(route('pos-imports.index'));

        $this->assertDatabaseHas('pos_imports', [
            'restaurant_id' => $restaurant->id,
            'status' => 'completed_with_errors',
            'rows_successful' => 5,
            'rows_failed' => 4,
        ]);
        foreach ([
            '1' => '2026-11-21 12:45:00',
            '2' => '2026-11-21 12:45:00',
            '3' => '2026-04-03 09:30:00',
            '5' => '2026-01-02 08:15:00',
            '7' => '2026-04-03 00:00:00',
        ] as $orderId => $orderedAt) {
            $this->assertDatabaseHas('orders', [
                'restaurant_id' => $restaurant->id,
                'external_order_id' => $orderId,
                'ordered_at' => $orderedAt,
            ]);
        }
        $this->assertDatabaseHas('customers', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Jane',
            'first_order_at' => '2026-11-21 12:45:00',
            'last_order_at' => '2026-11-21 12:45:00',
        ]);
        foreach ([5, 7, 9] as $rowNumber) {
            $this->assertDatabaseHas('pos_import_errors', [
                'row_number' => $rowNumber,
                'message' => 'The order date field must be a valid date.',
            ]);
        }
        $this->assertDatabaseMissing('orders', ['external_order_id' => '4']);
        $this->assertDatabaseMissing('orders', ['external_order_id' => '6']);
        $this->assertDatabaseMissing('orders', ['external_order_id' => '8']);
        $this->assertDatabaseMissing('orders', ['external_order_id' => '9']);
        $this->assertSame('31/02/2026 12:45', PosImportError::where('row_number', 5)->firstOrFail()->row_data['order_date']);
        $this->assertSame('21/11/2026 12:45', PosImportError::where('row_number', 10)->firstOrFail()->row_data['order_date']);
    }

    public function test_seeded_user_is_assigned_a_restaurant(): void
    {
        $this->seed();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertNotNull($user->restaurant_id);
        $this->assertDatabaseHas('restaurants', ['id' => $user->restaurant_id]);
    }
}
