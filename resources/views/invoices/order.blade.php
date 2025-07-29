<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة #{{ $order->id }}</title>
    <style>
        body {
            font-family: 'amiri';
            direction: rtl;
            text-align: right;
            padding: 40px;
            background: #fff;
            color: #333;
        }
        h1, h2, h3 {
            margin: 0;
        }
        .header {
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-table, .product-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td, .product-table th, .product-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .product-table th {
            background-color: #f0f0f0;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>فاتورة الطلب رقم #{{ $order->id }}</h2>
        <p>تاريخ: {{ $order->created_at->format('Y-m-d H:i') }}</p>
    </div>

    <div class="section">
        <h3>بيانات العميل</h3>
        <table class="info-table">
            <tr>
                <td><strong>الاسم:</strong> {{ $order->customer->name }}</td>
                <td><strong>الهاتف:</strong> {{ $order->customer->phone }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>العنوان:</strong> {{ $order->customer->address }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>تفاصيل المنتجات</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->pivot->quantity }}</td>
                        <td>{{ $product->price }} د.ل</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>الإجمالي</h3>
        <p><strong>{{ $order->total_price }} د.ل</strong></p>
    </div>

    <div class="footer">
        تم إنشاء هذه الفاتورة بواسطة النظام تلقائيًا — شكراً لتعاملكم معنا.
    </div>

</body>
</html>
