(function () {
    'use strict';

    var search = document.getElementById('ppTypeSearch');
    if (search) {
        var cards = Array.prototype.slice.call(document.querySelectorAll('.pp-type-card'));
        var groups = Array.prototype.slice.call(document.querySelectorAll('.pp-type-group'));
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            cards.forEach(function (card) {
                card.hidden = q !== '' && (card.getAttribute('data-search') || '').toLowerCase().indexOf(q) === -1;
            });
            groups.forEach(function (group) {
                var visible = group.querySelectorAll('.pp-type-card:not([hidden])').length;
                group.hidden = visible === 0;
            });
        });
    }

    var same = document.getElementById('same_address');
    if (same) {
        var copy = function () {
            if (!same.checked) return;
            ['address_bn', 'address_en', 'village_bn', 'village_en', 'rbs_bn', 'rbs_en', 'holding_no', 'ward', 'post_office_bn', 'post_office_en', 'union_bn', 'union_en', 'upazila_bn', 'upazila_en', 'district_bn', 'district_en', 'division_bn', 'division_en', 'postcode'].forEach(function (key) {
                var from = document.querySelector('[name="present[' + key + ']"]');
                var to = document.querySelector('[name="permanent[' + key + ']"]');
                if (from && to) to.value = from.value;
            });
        };
        same.addEventListener('change', copy);
        document.querySelectorAll('[name^="present["]').forEach(function (input) {
            input.addEventListener('input', copy);
            input.addEventListener('change', copy);
        });
        copy();
    }

    var addRows = function (type) {
        var tbody = document.getElementById(type === 'member' ? 'ppMemberRows' : 'ppFeeRows');
        var template = document.getElementById(type === 'member' ? 'ppMemberTemplate' : 'ppFeeTemplate');
        if (!tbody || !template) return;
        var row = template.content.firstElementChild.cloneNode(true);
        tbody.appendChild(row);
        renumber(tbody);
    };

    var renumber = function (tbody) {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr'), function (row, index) {
            var cell = row.querySelector('[data-row-no]');
            if (cell) cell.textContent = String(index + 1);
        });
    };

    document.querySelectorAll('[data-add-member]').forEach(function (button) {
        button.addEventListener('click', function () { addRows('member'); });
    });
    document.querySelectorAll('[data-add-fee]').forEach(function (button) {
        button.addEventListener('click', function () { addRows('fee'); });
    });

    document.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-row]');
        if (!remove) return;
        var row = remove.closest('tr');
        var tbody = row ? row.parentElement : null;
        if (row) row.remove();
        if (tbody && tbody.id === 'ppMemberRows' && tbody.children.length === 0) addRows('member');
        if (tbody && tbody.id === 'ppFeeRows' && tbody.children.length === 0) addRows('fee');
        if (tbody) renumber(tbody);
    });

    var fileInput = document.getElementById('ppPhoto');
    var fileLabel = document.getElementById('ppPhotoName');
    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', function () {
            fileLabel.textContent = fileInput.files && fileInput.files.length ? fileInput.files[0].name : 'কোনো ফাইল নির্বাচন করা হয়নি';
        });
    }
})();
