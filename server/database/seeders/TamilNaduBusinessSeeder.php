<?php

namespace Database\Seeders;

use App\Models\AiBusinessGoal;
use App\Models\AiBusinessProfile;
use App\Models\AiInsight;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerReminder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TamilNaduBusinessSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create or Find Owner User
            $user = User::firstOrCreate(
                ['email' => 'owner@tnretail.com'],
                [
                    'name'     => 'K. Senthilkumar',
                    'password' => Hash::make('password123'),
                    'phone'    => '+919840123456',
                ]
            );

            // 2. Create Business
            $business = Business::create([
                'name'     => 'Sri Murugan HyperMarket & Traders',
                'slug'     => 'sri-murugan-hypermarket',
                'type'     => 'retail',
                'category' => 'Grocery & General Store',
                'currency' => 'INR',
                'phone'    => '+919840123456',
                'email'    => 'srimurugan.tn@gmail.com',
                'address'  => 'No. 45, Nethaji Road, T. Nagar',
                'city'     => 'Chennai',
                'state'    => 'Tamil Nadu',
                'pincode'  => '600017',
            ]);

            // Link User to Business
            BusinessUser::create([
                'user_id'     => $user->id,
                'business_id' => $business->id,
                'role'        => 'owner',
                'is_active'   => true,
            ]);

            // Set as current business
            $user->update(['current_business_id' => $business->id]);

            // Authenticate context for BelongsToBusiness trait
            auth()->setUser($user);

            // 3. Categories
            $catRice = Category::create(['business_id' => $business->id, 'name' => 'Rice & Grains', 'slug' => 'rice-grains', 'color' => '#f59e0b']);
            $catSpices = Category::create(['business_id' => $business->id, 'name' => 'Spices & Masalas', 'slug' => 'spices-masalas', 'color' => '#ef4444']);
            $catDairy = Category::create(['business_id' => $business->id, 'name' => 'Dairy & Beverages', 'slug' => 'dairy-beverages', 'color' => '#3b82f6']);
            $catOils = Category::create(['business_id' => $business->id, 'name' => 'Cooking Oils', 'slug' => 'cooking-oils', 'color' => '#10b981']);
            $catPersonal = Category::create(['business_id' => $business->id, 'name' => 'Personal Care & Snacks', 'slug' => 'personal-care-snacks', 'color' => '#8b5cf6']);

            // 4. Products
            $productsData = [
                [
                    'category_id'         => $catRice->id,
                    'name'                => 'Ponni Raw Rice (26kg Bag)',
                    'sku'                 => 'RICE-PONNI-26K',
                    'barcode'             => '890100100011',
                    'unit'                => 'bag',
                    'purchase_price'      => 1350.00,
                    'selling_price'       => 1560.00,
                    'stock_quantity'      => 45,
                    'low_stock_threshold' => 10,
                ],
                [
                    'category_id'         => $catRice->id,
                    'name'                => 'Idli Rice Premium (10kg Bag)',
                    'sku'                 => 'RICE-IDLI-10K',
                    'barcode'             => '890100100022',
                    'unit'                => 'bag',
                    'purchase_price'      => 480.00,
                    'selling_price'       => 580.00,
                    'stock_quantity'      => 32,
                    'low_stock_threshold' => 8,
                ],
                [
                    'category_id'         => $catRice->id,
                    'name'                => 'Udhaiyam Toor Dhal (1kg)',
                    'sku'                 => 'DHAL-TOOR-1K',
                    'barcode'             => '890100100033',
                    'unit'                => 'kg',
                    'purchase_price'      => 140.00,
                    'selling_price'       => 165.00,
                    'stock_quantity'      => 3, // LOW STOCK TRIGGER!
                    'low_stock_threshold' => 10,
                ],
                [
                    'category_id'         => $catSpices->id,
                    'name'                => 'Aachi Garam Masala (100g)',
                    'sku'                 => 'SPICE-AACHI-100G',
                    'barcode'             => '890100200011',
                    'unit'                => 'pack',
                    'purchase_price'      => 38.00,
                    'selling_price'       => 48.00,
                    'stock_quantity'      => 110,
                    'low_stock_threshold' => 20,
                ],
                [
                    'category_id'         => $catSpices->id,
                    'name'                => 'Sakthi Chilly Powder (250g)',
                    'sku'                 => 'SPICE-SAKTHI-250G',
                    'barcode'             => '890100200022',
                    'unit'                => 'pack',
                    'purchase_price'      => 65.00,
                    'selling_price'       => 82.00,
                    'stock_quantity'      => 85,
                    'low_stock_threshold' => 15,
                ],
                [
                    'category_id'         => $catSpices->id,
                    'name'                => 'Tata Salt Vacuum Evaporated (1kg)',
                    'sku'                 => 'SALT-TATA-1K',
                    'barcode'             => '890100200033',
                    'unit'                => 'pack',
                    'purchase_price'      => 18.00,
                    'selling_price'       => 24.00,
                    'stock_quantity'      => 180,
                    'low_stock_threshold' => 30,
                ],
                [
                    'category_id'         => $catOils->id,
                    'name'                => 'Gold Winner Sunflower Oil (1L Pouch)',
                    'sku'                 => 'OIL-GOLD-1L',
                    'barcode'             => '890100300011',
                    'unit'                => 'pouch',
                    'purchase_price'      => 115.00,
                    'selling_price'       => 138.00,
                    'stock_quantity'      => 140,
                    'low_stock_threshold' => 25,
                ],
                [
                    'category_id'         => $catDairy->id,
                    'name'                => 'Narasu\'s Premium Filter Coffee (500g)',
                    'sku'                 => 'COFFEE-NARASU-500G',
                    'barcode'             => '890100400011',
                    'unit'                => 'pack',
                    'purchase_price'      => 180.00,
                    'selling_price'       => 220.00,
                    'stock_quantity'      => 38,
                    'low_stock_threshold' => 10,
                ],
                [
                    'category_id'         => $catDairy->id,
                    'name'                => 'Gemini Dust Tea Powder (250g)',
                    'sku'                 => 'TEA-GEMINI-250G',
                    'barcode'             => '890100400022',
                    'unit'                => 'pack',
                    'purchase_price'      => 90.00,
                    'selling_price'       => 115.00,
                    'stock_quantity'      => 55,
                    'low_stock_threshold' => 15,
                ],
                [
                    'category_id'         => $catDairy->id,
                    'name'                => 'Aavin Full Cream Milk (500ml)',
                    'sku'                 => 'MILK-AAVIN-500ML',
                    'barcode'             => '890100400033',
                    'unit'                => 'pouch',
                    'purchase_price'      => 22.00,
                    'selling_price'       => 25.00,
                    'stock_quantity'      => 90,
                    'low_stock_threshold' => 30,
                ],
                [
                    'category_id'         => $catPersonal->id,
                    'name'                => 'Haldiram\'s Nagpur Bhujia (200g)',
                    'sku'                 => 'SNACK-HALDIRAM-200G',
                    'barcode'             => '890100500011',
                    'unit'                => 'pack',
                    'purchase_price'      => 45.00,
                    'selling_price'       => 55.00,
                    'stock_quantity'      => 4, // LOW STOCK TRIGGER!
                    'low_stock_threshold' => 12,
                ],
                [
                    'category_id'         => $catPersonal->id,
                    'name'                => 'Hamam Neem Soap (100g Pack of 3)',
                    'sku'                 => 'SOAP-HAMAM-3P',
                    'barcode'             => '890100500022',
                    'unit'                => 'pack',
                    'purchase_price'      => 105.00,
                    'selling_price'       => 132.00,
                    'stock_quantity'      => 48,
                    'low_stock_threshold' => 12,
                ],
            ];

            $createdProducts = [];
            foreach ($productsData as $pData) {
                $pData['business_id'] = $business->id;
                $pData['is_active'] = true;
                $product = Product::create($pData);
                $createdProducts[$product->sku] = $product;

                // Initial Stock Movement
                StockMovement::create([
                    'business_id'  => $business->id,
                    'product_id'   => $product->id,
                    'user_id'      => $user->id,
                    'type'         => 'PURCHASE',
                    'quantity'     => $product->stock_quantity,
                    'unit_cost'    => $product->purchase_price,
                    'stock_before' => 0,
                    'stock_after'  => $product->stock_quantity,
                    'notes'        => 'Opening stock bulk import from vendor',
                ]);
            }

            // 5. Customers
            $customersData = [
                [
                    'name'         => 'R. Vijayaraghavan',
                    'phone'        => '+919884011223',
                    'email'        => 'vijay.r@gmail.com',
                    'address'      => 'Door No 18, Usman Road, T.Nagar',
                    'city'         => 'Chennai',
                    'state'        => 'Tamil Nadu',
                    'pincode'      => '600017',
                    'gstin'        => '33AAAPV1234A1Z5',
                    'credit_limit' => 25000.00,
                ],
                [
                    'name'         => 'Lakshmi Ammal Tiffins',
                    'phone'        => '+919443155667',
                    'email'        => 'lakshmi.tiffins@yahoo.com',
                    'address'      => '74, West Tower Street',
                    'city'         => 'Madurai',
                    'state'        => 'Tamil Nadu',
                    'pincode'      => '625001',
                    'gstin'        => '33ABCDE5678F1Z2',
                    'credit_limit' => 50000.00,
                ],
                [
                    'name'         => 'Saravana Bhavan Canteen',
                    'phone'        => '+919842288990',
                    'email'        => 'saravana.canteen@gmail.com',
                    'address'      => '102, Cross Cut Road, Gandhipuram',
                    'city'         => 'Coimbatore',
                    'state'        => 'Tamil Nadu',
                    'pincode'      => '641012',
                    'gstin'        => '33AAACS9988K1Z9',
                    'credit_limit' => 100000.00,
                ],
                [
                    'name'         => 'K. Arumugam & Bros',
                    'phone'        => '+919789044332',
                    'email'        => 'arumugambros@outlook.com',
                    'address'      => '12, Big Bazaar Street',
                    'city'         => 'Trichy',
                    'state'        => 'Tamil Nadu',
                    'pincode'      => '620008',
                    'credit_limit' => 75000.00,
                ],
                [
                    'name'         => 'Sundaram Supermarket',
                    'phone'        => '+919944077889',
                    'email'        => 'sundaram.salem@gmail.com',
                    'address'      => '88, Omalur Main Road',
                    'city'         => 'Salem',
                    'state'        => 'Tamil Nadu',
                    'pincode'      => '636007',
                    'credit_limit' => 40000.00,
                ],
            ];

            $createdCustomers = [];
            foreach ($customersData as $cData) {
                $cData['business_id'] = $business->id;
                $cData['is_active'] = true;
                $cust = Customer::create($cData);
                $createdCustomers[] = $cust;
            }

            // 6. Invoices & Payments (Realistic past 30 days transactions)

            // Invoice 1 - Fully Paid via UPI
            $inv1Date = Carbon::now()->subDays(20);
            $inv1 = Invoice::create([
                'business_id'     => $business->id,
                'invoice_number'  => 'INV-10001',
                'customer_id'     => $createdCustomers[0]->id,
                'user_id'         => $user->id,
                'customer_name'   => $createdCustomers[0]->name,
                'customer_phone'  => $createdCustomers[0]->phone,
                'date'            => $inv1Date,
                'subtotal'        => 3908.00,
                'discount_type'   => 'fixed',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'tax_percent'     => 0,
                'tax_amount'      => 0,
                'grand_total'     => 3908.00,
                'amount_paid'     => 3908.00,
                'balance_due'     => 0,
                'payment_method'  => 'UPI',
                'payment_status'  => 'PAID',
                'notes'           => 'Prompt payment via GPay UPI',
            ]);

            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv1->id, 'product_id' => $createdProducts['RICE-PONNI-26K']->id, 'product_name' => 'Ponni Raw Rice (26kg Bag)', 'unit' => 'bag', 'unit_price' => 1560.00, 'unit_cost' => 1350.00, 'quantity' => 2, 'total' => 3120.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv1->id, 'product_id' => $createdProducts['OIL-GOLD-1L']->id, 'product_name' => 'Gold Winner Sunflower Oil (1L Pouch)', 'unit' => 'pouch', 'unit_price' => 138.00, 'unit_cost' => 115.00, 'quantity' => 5, 'total' => 690.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv1->id, 'product_id' => $createdProducts['SPICE-AACHI-100G']->id, 'product_name' => 'Aachi Garam Masala (100g)', 'unit' => 'pack', 'unit_price' => 48.00, 'unit_cost' => 38.00, 'quantity' => 2, 'total' => 96.00]);

            Payment::create([
                'business_id'      => $business->id,
                'customer_id'      => $createdCustomers[0]->id,
                'invoice_id'       => $inv1->id,
                'payment_number'   => 'PAY-10001',
                'payment_date'     => $inv1Date,
                'amount'           => 3908.00,
                'payment_method'   => 'UPI',
                'reference_no'     => 'UPI/423188990011',
                'notes'            => 'GPay payment received',
            ]);

            // Invoice 2 - Partial Payment via Cash (Lakshmi Ammal Tiffins)
            $inv2Date = Carbon::now()->subDays(12);
            $inv2 = Invoice::create([
                'business_id'     => $business->id,
                'invoice_number'  => 'INV-10002',
                'customer_id'     => $createdCustomers[1]->id,
                'user_id'         => $user->id,
                'customer_name'   => $createdCustomers[1]->name,
                'customer_phone'  => $createdCustomers[1]->phone,
                'date'            => $inv2Date,
                'subtotal'        => 5430.00,
                'discount_type'   => 'fixed',
                'discount_value'  => 50.00,
                'discount_amount' => 50.00,
                'tax_percent'     => 0,
                'tax_amount'      => 0,
                'grand_total'     => 5380.00,
                'amount_paid'     => 3000.00,
                'balance_due'     => 2380.00,
                'payment_method'  => 'Cash',
                'payment_status'  => 'PARTIAL',
                'notes'           => 'Partial payment ₹3,000 cash at counter. Balance ₹2,380 due next week.',
            ]);

            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv2->id, 'product_id' => $createdProducts['RICE-IDLI-10K']->id, 'product_name' => 'Idli Rice Premium (10kg Bag)', 'unit' => 'bag', 'unit_price' => 580.00, 'unit_cost' => 480.00, 'quantity' => 5, 'total' => 2900.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv2->id, 'product_id' => $createdProducts['DHAL-TOOR-1K']->id, 'product_name' => 'Udhaiyam Toor Dhal (1kg)', 'unit' => 'kg', 'unit_price' => 165.00, 'unit_cost' => 140.00, 'quantity' => 10, 'total' => 1650.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv2->id, 'product_id' => $createdProducts['COFFEE-NARASU-500G']->id, 'product_name' => 'Narasu\'s Premium Filter Coffee (500g)', 'unit' => 'pack', 'unit_price' => 220.00, 'unit_cost' => 180.00, 'quantity' => 4, 'total' => 880.00]);

            Payment::create([
                'business_id'      => $business->id,
                'customer_id'      => $createdCustomers[1]->id,
                'invoice_id'       => $inv2->id,
                'payment_number'   => 'PAY-10002',
                'payment_date'     => $inv2Date,
                'amount'           => 3000.00,
                'payment_method'   => 'Cash',
                'reference_no'     => 'CASH-REF-02',
                'notes'            => 'Counter cash deposit',
            ]);

            CustomerReminder::create([
                'business_id'   => $business->id,
                'customer_id'   => $createdCustomers[1]->id,
                'user_id'       => $user->id,
                'amount'        => 2380.00,
                'reminder_date' => Carbon::now()->subDays(2),
                'status'        => 'PENDING',
                'notes'         => 'Vanakkam Lakshmi Ammal Tiffins! Friendly reminder regarding pending balance ₹2,380 for Invoice ' . $inv2->invoice_number,
            ]);

            // Invoice 3 - Pending Overdue (Saravana Bhavan Canteen)
            $inv3Date = Carbon::now()->subDays(15);
            $inv3 = Invoice::create([
                'business_id'     => $business->id,
                'invoice_number'  => 'INV-10003',
                'customer_id'     => $createdCustomers[2]->id,
                'user_id'         => $user->id,
                'customer_name'   => $createdCustomers[2]->name,
                'customer_phone'  => $createdCustomers[2]->phone,
                'date'            => $inv3Date,
                'subtotal'        => 19180.00,
                'discount_type'   => 'fixed',
                'discount_value'  => 0,
                'discount_amount' => 0,
                'tax_percent'     => 0,
                'tax_amount'      => 0,
                'grand_total'     => 19180.00,
                'amount_paid'     => 0.00,
                'balance_due'     => 19180.00,
                'payment_method'  => 'Credit',
                'payment_status'  => 'PENDING',
                'notes'           => 'Bulk credit delivery for Coimbatore branch canteen',
            ]);

            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv3->id, 'product_id' => $createdProducts['RICE-PONNI-26K']->id, 'product_name' => 'Ponni Raw Rice (26kg Bag)', 'unit' => 'bag', 'unit_price' => 1560.00, 'unit_cost' => 1350.00, 'quantity' => 10, 'total' => 15600.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv3->id, 'product_id' => $createdProducts['OIL-GOLD-1L']->id, 'product_name' => 'Gold Winner Sunflower Oil (1L Pouch)', 'unit' => 'pouch', 'unit_price' => 138.00, 'unit_cost' => 115.00, 'quantity' => 20, 'total' => 2760.00]);
            InvoiceItem::create(['business_id' => $business->id, 'invoice_id' => $inv3->id, 'product_id' => $createdProducts['SPICE-SAKTHI-250G']->id, 'product_name' => 'Sakthi Chilly Powder (250g)', 'unit' => 'pack', 'unit_price' => 82.00, 'unit_cost' => 65.00, 'quantity' => 10, 'total' => 820.00]);

            CustomerReminder::create([
                'business_id'   => $business->id,
                'customer_id'   => $createdCustomers[2]->id,
                'user_id'       => $user->id,
                'amount'        => 19180.00,
                'reminder_date' => Carbon::now()->subDays(1),
                'status'        => 'PENDING',
                'notes'         => 'Vanakkam Saravana Bhavan Canteen! Your invoice ' . $inv3->invoice_number . ' for ₹19,180 is now 15 days overdue. Kindly settle via UPI/Bank transfer.',
            ]);

            // 7. WhatsApp Templates
            WhatsAppTemplate::create([
                'business_id'   => $business->id,
                'type'          => 'INVOICE',
                'template_text' => 'Vanakkam {{customer_name}}, thank you for shopping at Sri Murugan HyperMarket! Your bill {{invoice_number}} for total ₹{{total_amount}} is ready. Download here: {{link}}',
                'is_active'     => true,
            ]);

            WhatsAppTemplate::create([
                'business_id'   => $business->id,
                'type'          => 'DEBT_REMINDER',
                'template_text' => 'Vanakkam {{customer_name}}, gentle reminder from Sri Murugan HyperMarket. Pending balance of ₹{{due_amount}} for Invoice {{invoice_number}} is due. Please pay via UPI to +919840123456.',
                'is_active'     => true,
            ]);

            // 8. AI Insights & Business Goals
            AiInsight::create([
                'business_id'    => $business->id,
                'type'           => 'INVENTORY_ALERT',
                'title'          => 'Critical Low Stock Warning',
                'severity'       => 'HIGH',
                'problem'        => 'Udhaiyam Toor Dhal (1kg) is down to 3 units! Haldiram\'s Bhujia has only 4 packs remaining.',
                'impact'         => 'Potential lost sales revenue of ₹4,500 over the coming weekend.',
                'recommendation' => 'Reorder immediately from your Chennai wholesale vendor.',
                'status'         => 'ACTIVE',
            ]);

            AiInsight::create([
                'business_id'    => $business->id,
                'type'           => 'PAYMENT_COLLECTION',
                'title'          => 'High Value Overdue Credit Alert',
                'severity'       => 'MEDIUM',
                'problem'        => 'Saravana Bhavan Canteen has an outstanding balance of ₹19,180 which is 15 days overdue.',
                'impact'         => 'Ties up working capital required for new stock purchases.',
                'recommendation' => 'Send WhatsApp automated payment reminders to customer.',
                'status'         => 'ACTIVE',
            ]);

            AiBusinessGoal::create([
                'business_id'    => $business->id,
                'title'          => 'Achieve ₹2.5 Lakhs Monthly Gross Retail Sales',
                'metric_key'     => 'monthly_sales_revenue',
                'baseline_value' => 0.00,
                'target_value'   => 250000.00,
                'current_value'  => 28468.00,
                'status'         => 'ACTIVE',
            ]);

            AiBusinessProfile::create([
                'business_id'   => $business->id,
                'profile_data'  => [
                    'industry'         => 'Grocery & FMCG Retail Trade',
                    'target_audience'  => 'Local households, tiffin centers, hotels in Chennai & Madurai',
                    'growth_stage'     => 'GROWTH',
                    'business_summary' => 'Sri Murugan HyperMarket is a premier retail & wholesale grocery hub in T. Nagar, Chennai offering premium Ponni rice, Aavin dairy, spices, and daily essentials with fast UPI and cash settlement.',
                ],
            ]);
        });
    }
}
