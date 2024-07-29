<?php

namespace Modules\Transfer\Tests\Unit;

use App\Enums\ItemTypesEnum;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Item\Models\Item;
use Modules\Stock\Models\Stock;

class TransfersTest extends TestCase
{
    use RefreshDatabase;
    public $standardItem;
    public $variantItem;
    public $transfer;

    public function setup(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->setupCompleted();

        $this->standardItem = $this->createInitItem();
        $this->variantItem = $this->createInitItem(ItemTypesEnum::VARIABLE, 'kg', 60, 90);
        $this->transfer = $this->createTransfer(['from_warehouse_id' => 3, 'to_warehouse_id' => 4]);
        $this->createOwner();
    }

    public function test_can_list_transfers()
    {
        $res = $this->get(route('api.transfers.index'))->json();
        $this->assertEquals(1, count($res['data']));
        $this->assertEquals(1, $res['meta']['total']);
    }

    public function test_can_not_create_transfer_without_required_inputs()
    {
        $res = $this->post(route('api.transfers.store'), [])
            ->assertStatus(422)
            ->withExceptions(collect(ValidationException::class))
            ->assertJsonValidationErrorFor('date', 'payload')
            ->assertJsonValidationErrorFor('from_warehouse_id', 'payload')
            ->assertJsonValidationErrorFor('to_warehouse_id', 'payload')
            ->assertJsonValidationErrorFor('details', 'payload')
            ->json();

        $this->assertEquals($res['payload']['details'][0], __('validation.custom.details.required'));
        $this->assertFalse($res['success']);
    }

    public function test_can_create_transfer_with_standard_item()
    {
        $tax_details = Item::getTaxDetails($this->standardItem);

        $detail_1 = $this->createDetail([
            'detailable_id' => null,
            'detailable_type' => null,
            'warehouse_id' => 1,
            'variant_id' => null,
            'item_id' => $this->standardItem->id,
            'quantity' => 35,
            'unit_id' => $this->standardItem->purchase_unit_id,
            'product_type' => $this->standardItem->product_type,

            'type' => $this->standardItem->type,
            'production_date' => null,
            'expired_date' => null,
        ])->toArray();

        $pipelineId = $this->createPipeline()->id;

        $res = $this->post(route('api.transfers.store'), [
            'date' => date('Y-m-d'),
            'from_warehouse_id' => 1,
            'to_warehouse_id' => 2,
            'delegate_id' => $this->createDelegate()->id,
            'discount_type' => 1,
            'discount' => 0,
            'commission_type' => 1,
            'shipping' => 0,
            'other_expenses' => 0,
            'pipeline_id' => $pipelineId,
            'stage_id' => $this->storeStage(['pipeline_id' => $pipelineId, 'complete' => 100])->id,
            'grand_total' => $tax_details['total_cost'] * 2,
            'tax' => 0,
            'tax_net' => 0,
            'details' => [$detail_1]
        ])->json();

        $this->assertDatabaseCount('transfers', 2);
        $this->assertDatabaseHas('transfers', [
            'from_warehouse_id' => 1,
            'to_warehouse_id' => 2,
        ]);

        $this->assertDatabaseCount('details', 1);
        $this->assertDatabaseHas('details', [
            'item_id' => $this->standardItem->id,
            'variant_id' => null,
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->standardItem->id,
            'variant_id' => null,
            'warehouse_id' => 1,
            'quantity' => -35
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->standardItem->id,
            'variant_id' => null,
            'warehouse_id' => 2,
            'quantity' => 35
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals($res['payload'], __('status.created', ['name' => sprintf('%07d', 2), 'module' => __('modules.transfer')]));
    }

    public function test_can_create_transfer_with_variant_item()
    {
        $tax_details = Item::getTaxDetails($this->variantItem, $this->variantItem->variants->first());

        $detail_1 = $this->createDetail([
            'detailable_id' => null,
            'detailable_type' => null,
            'warehouse_id' => 3,
            'variant_id' => $this->variantItem->variants->first()->id,
            'item_id' => $this->variantItem->id,
            'quantity' => 22,
            'unit_id' => $this->variantItem->purchase_unit_id,
            'product_type' => $this->variantItem->product_type,
            'type' => $this->variantItem->type,
            'production_date' => null,
            'expired_date' => null,
        ])->toArray();

        $pipelineId = $this->createPipeline()->id;
        $res = $this->post(route('api.transfers.store'), [
            'date' => date('Y-m-d'),
            'from_warehouse_id' => 3,
            'to_warehouse_id' => 4,
            'delegate_id' => $this->createDelegate()->id,
            'discount_type' => 1,
            'discount' => 0,
            'commission_type' => 1,
            'shipping' => 0,
            'other_expenses' => 0,
            'pipeline_id' => $pipelineId,
            'stage_id' => $this->storeStage(['pipeline_id' => $pipelineId, 'complete' => 100])->id,
            'grand_total' => $tax_details['total_cost'] * 2,
            'tax' => 0,
            'tax_net' => 0,
            'details' => [$detail_1]
        ])->json();
        
        $this->assertDatabaseCount('transfers', 2);
        $this->assertDatabaseHas('transfers', [
            'from_warehouse_id' => 3,
            'to_warehouse_id' => 4,
        ]);

        $this->assertDatabaseCount('details', 1);
        $this->assertDatabaseHas('details', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
        ]);
        
        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => 3,
            'quantity' => -22
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => 4,
            'quantity' => 22
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals($res['payload'], __('status.created', ['name' => sprintf('%07d', 2), 'module' => __('modules.transfer')]));
    }

    public function test_can_edit_transfer_and_add_a_new_details()
    {
        $transfer = $this->transfer;
        $transferId = $transfer->id;
        
        $detail_1 = $this->createDetail([
            'detailable_id' => null,
            'detailable_type' => null,
            'warehouse_id' => $transfer->from_warehouse_id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'item_id' => $this->variantItem->id,
            'quantity' => 22,
            'unit_id' => $this->variantItem->purchase_unit_id,
            'product_type' => $this->variantItem->product_type,
            'type' => $this->variantItem->type,
            'production_date' => null,
            'expired_date' => null,
        ])->toArray();

        $pipelineId = $this->createPipeline()->id;

        $res = $this->put(
            route('api.transfers.update', ['transfer' => $transferId]),
            [
                'date' => date('Y-m-d'),
                'from_warehouse_id' => $transfer->from_warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'delegate_id' => $this->createDelegate()->id,
                'discount_type' => 1,
                'discount' => 0,
                'commission_type' => 1,
                'shipping' => 0,
                'other_expenses' => 0,
                'pipeline_id' => $pipelineId,
                'stage_id' => $this->storeStage(['pipeline_id' => $pipelineId, 'complete' => 100])->id,
                'grand_total' => 100,
                'tax' => 0,
                'tax_net' => 0,
                'details' => [$detail_1],
                'deletedDetails' => []
            ]
        )->json();
        
        $this->assertDatabaseCount('transfers', 1);
        $this->assertDatabaseHas('transfers', [
            'from_warehouse_id' => $transfer->from_warehouse_id,
            'to_warehouse_id' => $transfer->to_warehouse_id,
        ]);

        $this->assertDatabaseCount('details', 1);
        $this->assertDatabaseHas('details', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => $transfer->from_warehouse_id,
            'quantity' => -22
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'quantity' => 22
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals($res['payload'], __('status.updated', ['name' => sprintf('%07d', $transferId), 'module' => __('modules.transfer')]));
    }

    public function test_can_edit_transfer_and_remove_a_old_detail()
    {
        $warehouseId = $this->createWarehouse()->id;

        $old_detail = $this->createDetail([
            'detailable_id' => null,
            'detailable_type' => null,
            'warehouse_id' => $warehouseId,
            'variant_id' => $this->variantItem->variants->first()->id,
            'item_id' => $this->variantItem->id,
            'quantity' => 11,
            'unit_id' => $this->variantItem->purchase_unit_id,
            'product_type' => $this->variantItem->product_type,
            'type' => $this->variantItem->type,
            'production_date' => null,
            'expired_date' => null,
        ])->toArray();

        $pipelineId = $this->createPipeline()->id;
        $stageId = $this->storeStage(['pipeline_id' => $pipelineId, 'complete' => 100])->id;

        $transfer = $this->createTransfer(['from_warehouse_id' => $warehouseId, 'to_warehouse_id' => 1, 'pipeline_id' => $pipelineId, 'stage_id' => $stageId,]);

        $this->createStock([
            'warehouse_id' => $warehouseId,
            'variant_id' => $this->variantItem->variants->first()->id,
            'item_id' => $this->variantItem->id,
            'quantity' => 55,
        ]);

        $transfer->details()->create($old_detail);

        $transferId = $transfer->id;
        $detail_1 = $this->createDetail([
            'detailable_id' => null,
            'detailable_type' => null,
            'variant_id' => $this->variantItem->variants->first()->id,
            'item_id' => $this->variantItem->id,
            'quantity' => 22,
            'unit_id' => $this->variantItem->purchase_unit_id,
            'product_type' => $this->variantItem->product_type,
            'type' => $this->variantItem->type,
            'production_date' => null,
            'expired_date' => null,
        ])->toArray();

        $pipelineId = $this->createPipeline()->id;

        $res = $this->put(
            route('api.transfers.update', ['transfer' => $transferId]),
            [
                'date' => date('Y-m-d'),
                'from_warehouse_id' => $warehouseId,
                'to_warehouse_id' => 1,
                'delegate_id' => $this->createDelegate()->id,
                'discount' => 0,
                'discount_type' => 1,
                'shipping' => 0,
                'commission_type' => 1,
                'other_expenses' => 0,
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'grand_total' => 100,
                'tax' => 0,
                'tax_net' => 0,
                'details' => [$detail_1],
                'deletedDetails' => [$transfer->details->first()->toArray()]
            ]
        )->json();

        $this->assertDatabaseCount('transfers', 2);
        $this->assertDatabaseHas('transfers', [
            'from_warehouse_id' => $warehouseId,
            'to_warehouse_id' => 1,
        ]);

        $this->assertDatabaseCount('details', 1);
        $this->assertDatabaseHas('details', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => $warehouseId,
            'quantity' => 44
        ]);

        $this->assertDatabaseHas('stock', [
            'item_id' => $this->variantItem->id,
            'variant_id' => $this->variantItem->variants->first()->id,
            'warehouse_id' => 1,
            'quantity' => 22
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals($res['payload'], __('status.updated', ['name' => sprintf('%07d', $transferId), 'module' => __('modules.transfer')]));
    }

    public function test_can_show_transfer()
    {
        $transferId = $this->transfer->id;
        $res = $this->get(route('api.transfers.show', ['transfer' => $transferId]))->json();
        $this->assertEquals(1, count($res));
        $this->assertEquals($transferId, $res['data']['id']);
    }

    public function test_can_delete_transfer()
    {
        $transferId = $this->transfer->id;
        $res = $this->delete(route('api.transfers.destroy', ['transfer' => $transferId]))->json();
        $this->assertTrue($res['success']);
        $this->assertEquals($res['payload'], __('status.deleted', ['name' => sprintf('%07d', $transferId), 'module' => __('modules.transfer')]));
    }
}
