/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var $ = jQuery;
var BookingDaysController = function () {
    this.none = $("#booking-days-container input[value='NONE']");
    this.others = $("#booking-days-container input:not([value='NONE'])");
    this.daysChecked = {};
    var self = this;
    this.none.change(function () {
        if ($(this).prop('checked')) {
            self.daysChecked = {};
        }
        self.apply();
    });
    this.others.change(function () {
        self.daysChecked[$(this).prop('value')] = $(this).prop('checked');
        self.apply();
    });
    this.apply();
};
BookingDaysController.prototype.isAnyDayChecked = function () {
    for (var key in this.daysChecked) {
        if (this.daysChecked[key]) {
            return true;
        }
    }
    return false;
};
BookingDaysController.prototype.apply = function () {
    if (this.isAnyDayChecked()) {
        this.clearNone();
    } else {
        this.setNone();
    }
};
BookingDaysController.prototype.clearNone = function () {
    this.none.prop('checked', false);
};
BookingDaysController.prototype.setNone = function () {
    this.others.prop('checked', false);
    this.none.prop('checked', true);
};
BookingDaysController.create = function () {
    return new BookingDaysController();
};

var DocumentTypeController = function () {
    this.documentTypeInput = $('#document-type');
    this.nifInput = $('#nif');
    var self = this;
    this.documentTypeInput.change(function () {
        self.apply();
    });
    self.apply();
};
DocumentTypeController.prototype.apply = function () {
    var value = this.documentTypeInput.val();
    if (value === 'UUID') {
        this.nifInput.prop('disabled', true);
    } else {
        this.nifInput.prop('disabled', false);
    }
};

var AgeController = function () {
    this.dateInput = $('#booking-birth-date');
    this.minorContainer = $('#booking-under-age');
    var self = this;
    this.dateInput.change(function () {
        self.apply();
    });
    this.apply();
};
AgeController.prototype.apply = function () {
    var birth = new Date(this.dateInput.val());
    if (isNaN(birth.getTime())) {
        this.showMinor(false);
    } else {
        this.showMinor(AgeController.isMinor(birth));
    }
};
AgeController.prototype.showMinor = function (show) {
    if (show) {
        this.minorContainer.show();
    } else {
        this.minorContainer.hide();
    }
};
// Computes age in years; birth must be a Date
AgeController.isMinor = function (birth) {
    var refdate = moment(
        abacoClientValidationParams.ageReferenceDate, 'DD-MM-YYYY');
    var age = refdate.diff(birth, 'years');
    return age < abacoClientValidationParams.minorityAge;
};
AgeController.create = function () {
    return new AgeController();
};

$(document).ready(function () {
    BookingDaysController.create();
    var ageController = AgeController.create();
    ageController.dateInput.datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        maxDate: 0,
        yearRange: '-100:+0'
    });
    new DocumentTypeController();
});
