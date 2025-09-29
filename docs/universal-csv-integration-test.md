# Universal CSV Template Test

Test the universal CSV template functionality:

1. **Template Structure**: 
   - Includes customer data (name, email, phone)
   - Includes product data (name, category, selling/cost prices)
   - Includes transaction data (quantity, date, payment method)
   - Includes BOM data (materials, quantities, costs)

2. **Frontend Changes**:
   - Removed data type selector
   - Updated modal title to "Import Data Universal"
   - Changed button text to "Unduh Template Universal"
   - Updated function names to `downloadUniversalTemplate`

3. **Backend Changes**:
   - Added `downloadUniversalTemplate` method in DataFeedController
   - Added `generateUniversalPreview` method in DataFeedService
   - Added `commitUniversalPreview` method for processing
   - Updated routes to include universal template endpoint

4. **Data Integration**:
   - Universal CSV format processes multiple data types in single import
   - Auto-creates products when enabled
   - Handles customer information (basic structure)
   - Creates staging records for transaction processing
   - Synchronizes data across all sections

## Test Steps:
1. Login to the application
2. Go to Data Feeds page
3. Click "Import CSV" button
4. Download the universal template
5. Fill with sample data
6. Upload and preview
7. Enable auto-create products if needed
8. Process the import
9. Verify data appears in products, transactions, and customers sections

## Sample Data Format:
```csv
transaction_date,customer_name,customer_email,customer_phone,product_name,product_category,quantity,unit,selling_price,discount,tax_amount,shipping_cost,payment_method,notes,product_cost_price,material_name,material_quantity,material_unit,material_cost_per_unit
2024-01-15,John Doe,john@example.com,081234567890,Nasi Goreng Spesial,Makanan,2,Porsi,25000,0,2500,5000,Cash,Pesanan untuk acara kantor,18000,Beras,0.5,Kg,12000
```