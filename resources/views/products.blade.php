<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Product Inventory</h1>

    <div class="card mb-4">
        <div class="card-header">Add Product</div>
        <div class="card-body">
            <form id="product-form" novalidate>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="name">Product name</label>
                        <input type="text" class="form-control" id="name" name="name">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="quantity">Quantity in stock</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="price">Price per item</label>
                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Submitted Products</div>
        <div class="card-body">
            <table class="table table-striped table-bordered mb-0" id="products-table">
                <thead>
                    <tr>
                        <th>Product name</th>
                        <th>Quantity in stock</th>
                        <th>Price per item</th>
                        <th>Datetime submitted</th>
                        <th>Total value number</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot></tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="edit-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="edit-form" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label" for="edit-name">Product name</label>
                        <input type="text" class="form-control" id="edit-name" name="name">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-quantity">Quantity in stock</label>
                        <input type="number" class="form-control" id="edit-quantity" name="quantity" min="0">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="edit-price">Price per item</label>
                        <input type="number" class="form-control" id="edit-price" name="price" min="0" step="0.01">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var editModal = new bootstrap.Modal('#edit-modal');
    var products = [];

    function renderTable() {
        var tbody = $('#products-table tbody').empty();
        var tfoot = $('#products-table tfoot').empty();
        var sum = 0;

        $.each(products, function (i, product) {
            var total = product.quantity * product.price;
            sum += total;

            var row = $('<tr>');
            row.append($('<td>').text(product.name));
            row.append($('<td>').text(product.quantity));
            row.append($('<td>').text(Number(product.price).toFixed(2)));
            row.append($('<td>').text(product.submitted_at));
            row.append($('<td>').text(total.toFixed(2)));
            row.append($('<td>').append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-sm btn-outline-secondary edit-btn')
                    .data('id', product.id)
                    .text('Edit')
            ));
            tbody.append(row);
        });

        if (products.length) {
            tfoot.append(
                $('<tr>').addClass('table-secondary fw-bold')
                    .append($('<td>').attr('colspan', 4).text('Sum total'))
                    .append($('<td>').text(sum.toFixed(2)))
                    .append($('<td>'))
            );
        } else {
            tbody.append(
                $('<tr>').append(
                    $('<td>').attr('colspan', 6).addClass('text-center text-muted').text('No products submitted yet')
                )
            );
        }
    }

    function showErrors(form, errors) {
        $(form).find('.is-invalid').removeClass('is-invalid');
        $.each(errors || {}, function (field, messages) {
            var input = $(form).find('[name="' + field + '"]');
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }

    function loadProducts() {
        $.get('products', function (data) {
            products = data;
            renderTable();
        });
    }

    $('#product-form').on('submit', function (e) {
        e.preventDefault();
        var form = this;

        $.post('products', {
            name: $('#name').val(),
            quantity: $('#quantity').val(),
            price: $('#price').val()
        }).done(function (data) {
            products = data;
            renderTable();
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                showErrors(form, xhr.responseJSON.errors);
            }
        });
    });

    $('#products-table').on('click', '.edit-btn', function () {
        var id = $(this).data('id');
        var product = null;

        $.each(products, function (i, p) {
            if (p.id === id) {
                product = p;
            }
        });

        if (!product) {
            return;
        }

        $('#edit-id').val(product.id);
        $('#edit-name').val(product.name);
        $('#edit-quantity').val(product.quantity);
        $('#edit-price').val(product.price);
        $('#edit-form').find('.is-invalid').removeClass('is-invalid');
        editModal.show();
    });

    $('#edit-form').on('submit', function (e) {
        e.preventDefault();
        var form = this;

        $.post('products/' + $('#edit-id').val(), {
            name: $('#edit-name').val(),
            quantity: $('#edit-quantity').val(),
            price: $('#edit-price').val()
        }).done(function (data) {
            products = data;
            renderTable();
            editModal.hide();
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                showErrors(form, xhr.responseJSON.errors);
            }
        });
    });

    loadProducts();
});
</script>
</body>
</html>
