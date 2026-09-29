# Catalog Data Import Template

Recommended CSV columns:

`sku,product_name,variant_name,brand,category,subcategory,description,unit,pack_size,mrp,selling_price,tax_class,stock,warehouse,grade,size,color,status`

## Import rules
- SKU must be unique.
- Category must resolve to a valid category.
- Brand must resolve to a valid brand.
- Numeric fields must be validated.
- Do not silently overwrite prices without reporting changes.
- Import must provide a preview.
- Invalid rows must produce actionable errors.
- Large imports should run asynchronously.
