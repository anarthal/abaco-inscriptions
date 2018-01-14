/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var $ = jQuery;
var BookingDaysController = function() {
    this.none = $("#booking-days-container input[value='NONE']");
    this.others = $("#booking-days-container input:not([value='NONE'])");
    this.daysChecked = {};
    var self = this;
    this.none.change(function() {
        if ($(this).attr('checked')) {
            self.daysChecked = {};
        }
        self.apply();
    });
    this.others.change(function() {
        self.daysChecked[$(this).attr('value')] = $(this).attr('checked');
        self.apply();
    });
    this.apply();
};
BookingDaysController.prototype.isAnyDayChecked = function() {
    for (var key in this.daysChecked) {
        if (this.daysChecked[key]) {
            return true;
        }
    }
    return false;
};
BookingDaysController.prototype.apply = function() {
    if (this.isAnyDayChecked()) {
        this.clearNone();
    } else {
        this.setNone();
    }
};
BookingDaysController.prototype.clearNone = function() {
    this.none.attr('checked', false);
};
BookingDaysController.prototype.setNone = function() {
    this.others.attr('checked', false);
    this.none.attr('checked', true);
};
BookingDaysController.create = function() {
    return new BookingDaysController();
};

var DocumentTypeController = function() {
    this.documentTypeInput = $('#document-type');
    this.nifInput = $('#nif');
    var self = this;
    this.documentTypeInput.change(function() {
        self.apply();
    });
    self.apply();
};
DocumentTypeController.prototype.apply = function() {
    var value = this.documentTypeInput.val();
    if (value === 'UUID') {
        this.nifInput.prop('disabled', true);
    } else {
        this.nifInput.prop('disabled', false);
    }
};

var AgeController = function() {
    this.dateInput = $('#booking-birth-date');
    this.minorContainer = $('#booking-under-age');
    var self = this;
    this.dateInput.change(function() {
        self.apply();
    });
    this.apply();
};
AgeController.prototype.apply = function() {
    var birth = new Date(this.dateInput.val());
    if (isNaN(birth.getTime())) {
        this.showMinor(false);
    } else {
        this.showMinor(AgeController.isMinor(birth));
    }
};
AgeController.prototype.showMinor = function(show) {
    if (show) {
        this.minorContainer.show();
    } else {
        this.minorContainer.hide();
    }
};
// Computes age in years; birth must be a Date
AgeController.isMinor = function(birth) {
    var age = moment().diff(birth, 'years');
    return age < abacoClientValidationParams.minorityAge;
};
AgeController.create = function() {
    return new AgeController();
};



var ActivityView = function() {
    this.male = $("#activity-participants-male");
    this.female = $("#activity-participants-female");
    this.total = $("#activity-participants-total");
    this.indifferent = $("#activity-participants-indifferent");
    this.error = $("#activity-error");
};
ActivityView.prototype.setController = function (controller) {
    this.controller = controller;
    var self = this;
    var FIELDS = ['male', 'female', 'total'];
    for (var i = 0; i !== FIELDS.length; ++i) {
        this[FIELDS[i]].change(function() {
            for (var j = 0; j !== FIELDS.length; ++j) {
                var field = FIELDS[j];
                self.controller[field] = self[field].val();
            }
            self.controller.notify();
        });
    }
    this.controller.notify();
};
ActivityView.prototype.setError = function(msg) {
    this.error.html(msg);
};
ActivityView.prototype.setValues = function(values) {
    this.male.val(values.male);
    this.female.val(values.female);
    this.total.val(values.total);
    this.indifferent.html(values.indifferent);
    this.setError("");
};

var ActivityController = function(view) {
    this.male = 0;
    this.female = 0;
    this.total = 0;
    this.view = view;
    view.setController(this);
};
ActivityController.prototype.indifferent = function() {
    return this.total - this.male - this.female;
};
ActivityController.prototype.notify = function() {
    var indiff = this.indifferent();
    if (indiff < 0) {
        this.view.setError(abacoClientValidationParams.totalParticipantsNegative);
    } else {
        this.view.setValues({
            male: this.male,
            female: this.female,
            total: this.total,
            indifferent: indiff
        });
    }
};
ActivityController.createDefault = function() {
    return new ActivityController(new ActivityView());
};

$(document).ready(function() {
    BookingDaysController.create();
    var ageController = AgeController.create();
    ageController.dateInput.datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        maxDate: 0,
        yearRange: '-100:+0'
    });
    ActivityController.createDefault();
    new DocumentTypeController();
});
