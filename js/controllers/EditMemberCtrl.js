angular.module('EditMemberCtrl', []).controller('EditMemberCtrl', ['$scope', '$http', '$location', '$route', '$routeParams', '$q', 'AuthService', function($scope, $http, $location, $route, $routeParams, $q, AuthService) {
    $scope.searchFilter = "";

    $scope.datepicker = {
        options: {
          formatYear: 'yy',
          minDate: new Date(1800, 1, 1),
          startingDay: 1
        },
        opened: false
      };

     $scope.datepicker2 = {
          options: {
            formatYear: 'yy',
            minDate: new Date(1800, 1, 1),
            startingDay: 1
          },
          opened: false
        };

     $scope.datepicker3 = {
            options: {
              formatYear: 'yy',
              minDate: new Date(1800, 1, 1),
              startingDay: 1
            },
            opened: false
          };
    $scope.datepicker4 = {
              options: {
                formatYear: 'yy',
                minDate: new Date(1800, 1, 1),
                startingDay: 1
              },
              opened: false
            };

    $scope.datepicker5 = {
                options: {
                  formatYear: 'yy',
                  minDate: new Date(1800, 1, 1),
                  startingDay: 1
                },
                opened: false
              };

      $scope.formatTime = function (t) {
        return new Date(t.getTime() - (t.getTimezoneOffset() * 60000)).toISOString().substring(11, 19);
      }

      $scope.openDatepicker = function() {
        $scope.datepicker.opened = !$scope.datepicker.opened;
      };

      $scope.openDatepicker2 = function() {
        $scope.datepicker2.opened = !$scope.datepicker2.opened;
      };

      $scope.openDatepicker3 = function() {
        $scope.datepicker3.opened = !$scope.datepicker3.opened;
      };

      $scope.openDatepicker4 = function() {
        $scope.datepicker4.opened = !$scope.datepicker4.opened;
      };

      $scope.openDatepicker5 = function() {
        $scope.datepicker5.opened = !$scope.datepicker5.opened;
      };

    $scope.areChangesPending= false;

    $scope.trainingFields = [
        { field: 'nims100',     label: 'NIMS-100'    },
        { field: 'nims200',     label: 'NIMS-200'    },
        { field: 'nims700',     label: 'NIMS-700'    },
        { field: 'nims800',     label: 'NIMS-800'    },
        { field: 'ics5',        label: 'ICS-0005'    },
        { field: 'atropine',    label: 'Atropine'    },
        { field: 'albuterol',   label: 'Albuterol'   },
        { field: 'epiniperine', label: 'Epinephrine' },
        { field: 'glucometry',  label: 'Glucometry'  },
    ];

    $scope.crewPositions = [
        { field: 'attendant',       label: 'Attendant'               },
        { field: 'crewchief',       label: 'Crew-Chief'              },
        { field: 'cctrainer',       label: 'Crew-Chief Trainer'      },
        { field: 'driver',          label: 'Driver'                  },
        { field: 'drivertrainer',   label: 'Driver Trainer'          },
        { field: 'dutysup',         label: 'Duty Supervisor'         },
        { field: 'ees',             label: 'Event EMS Supervisor'    },
        { field: 'firstresponsecc', label: 'FR-59 Crew Chief'        },
        { field: 'backupcc',        label: 'Probationary Crew Chief' },
        { field: 'backupdriver',    label: 'Probationary Driver'     }
    ];

    $scope.stateCodes = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID',
        'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS',
        'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK',
        'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV',
        'WI', 'WY'
    ];

    $scope.months = [
        'January', 'February', 'March', 'April', 'May', 'June', 'July',
        'August', 'September', 'October', 'November', 'December'
    ];

    $scope.positions = [
        { field: 'captain',        label: 'Captain'                  },
        { field: 'firstlt',        label: 'First Lt'                 },
        { field: 'secondlt',       label: 'Second Lt'                },
        { field: 'pres',           label: 'President'                },
        { field: 'vicepres',       label: 'Vice President'           },
        { field: 'schedco',        label: 'Scheduling Coordinator'   },
        { field: 'radioco',        label: 'Radio Coordinator'        },
        { field: 'traincommchair', label: 'Training Committee Chair' },
        { field: 'cprco',          label: 'CPR Coordinator'          },
        { field: 'aedco',          label: 'AED Coordinator'          },
        { field: 'webmaster',      label: 'Webmaster'                }
    ];

    $scope.yesNoQuestions = [
        // { field: '', label: 'Can create games/events?', yes: 'Yes', no: 'No' },
        // { field: '', label: 'Can manage content?', yes: 'Yes', no: 'No' },
        { field: 'admin',          label: 'Is a full admin?', yes: 'Yes',            no: 'No'             },
        { field: 'access_revoked', label: 'Website Access',   yes: 'Access Revoked', no: 'Access Granted' },
        { field: 'active',         label: 'Member Status',    yes: 'Active',         no: 'Inactive'       }
    ];

    var NUMBERS = [
        'rin', 'radionum', 'emt_num'
    ];

    var DATES = [
        'dob', 'dl_exp', 'emt_exp', 'cpr_exp', 'cevo_date'
    ];

    $scope.chooseMember = function (member) {
        $location.path('/edit-member/' + member.id);
    };

    $scope.clearSelected = function (member) {
        $location.path('/edit-member');
    };

    $scope.feelingLucky = function () {
        $scope.chooseMember($scope.members[Math.floor(Math.random() * $scope.members.length)]);
    };

    function loadData () {
        AuthService.getUserMetadata().then(function (data) {
            if (data.admin == 1 || data.captain == 1 || data.firstlt == 1 || data.secondlt == 1 || data.vicepres == 1 || data.president == 1) {
                if($routeParams.memberId) {
                    $http.get('member_table.php?member_id=' + $routeParams.memberId).then(function (response) {
                        $scope.selectedMember = response.data[0];
                        memberPostProcessing($scope.selectedMember);
                        $scope.selectedMember.access_revoked = parseInt($scope.selectedMember.access_revoked);
                        $scope.selectedMember.admin = parseInt($scope.selectedMember.admin);
                        $scope.selectedMember.active = parseInt($scope.selectedMember.active);

                    });
                } else {
                    $http.get('member_table.php?include_inactive').then(function (response) {
                        $scope.members = response.data;
                        memberlistPostProcessing();
                    });
                }
            } else{
                $location.path('/404');
            }
        });


    }
    loadData();

    function memberlistPostProcessing () {
        for (var i = 0; i < $scope.members.length; i++) {
            $scope.members[i] = memberPostProcessing($scope.members[i]);
        }
    }

    function memberPostProcessing (member) {
        for(var j = 0; j < DATES.length; j++) {
            if(member[DATES[j]] !== null) {
                member[DATES[j]] = new Date(member[DATES[j]]);
            }
        }

        for(var j = 0; j < NUMBERS.length; j++) {
            member[NUMBERS[j]] = parseInt(member[NUMBERS[j]]);
        }

        if(member.emt_level === '') {
            member.emt_level = 'Not an EMT';
        }

        if(!member.access_revoked) {
            member.access_revoked = 0;
        }

        if(member.admin == 1 && member.captain == 0) {
            member.webmaster = '1';
        }

        for (var j = 0; j < $scope.positions.length; j++) {
            if(member[$scope.positions[j].field] == 1) {
                member.position = $scope.positions[j].field;
                break;
            }
        }

        return member;
    }

    $scope.changeMade = function() {
        $scope.areChangesPending = true;
        console.log("change!")
    }


    $scope.save = function () {
        if(!$scope.areChangesPending) {
            return;
        }

        $scope.selectedMember.id = $routeParams.memberId;


        var toSubmit = $scope.selectedMember;

        // console.log("TS1", toSubmit);

        toSubmit.access_revoked = toSubmit.access_revoked.toString();
        toSubmit.admin = toSubmit.admin.toString();
        toSubmit.active = toSubmit.active.toString();

        // console.log("TS2", toSubmit);

        if(toSubmit.position === 'webmaster') {
            toSubmit.admin = '1';
            toSubmit.captain = '0';
        } else if(toSubmit.position) {
            toSubmit[toSubmit.position] = '1';
        }

        for(var j = 0; j < DATES.length; j++) {
            if(toSubmit[DATES[j]] !== null) {
                if(typeof toSubmit[DATES[j]] === 'number') {
                    toSubmit[DATES[j]] = new Date(toSubmit[DATES[j]]).toISOString().substring(0, 10);
                } else if(toSubmit[DATES[j]] instanceof Date) {
                    toSubmit[DATES[j]] = toSubmit[DATES[j]].toISOString().substring(0, 10);
                } else {
                    toSubmit[DATES[j]] = null;
                }
            }
        }



        var data = 'data=' + JSON.stringify(toSubmit) + '&session_id=' + $scope.getSessionIDCookie();

        $http({
            method: 'POST',
            url: '.edit_member.php',
            data: data, // pass in data as strings
            headers: {'Content-Type': 'application/x-www-form-urlencoded'} // set the headers so angular passing info as form data (not request payload)
        }).then(function (response) {
            if (!response.data.success) {
                console.log("it failed!");
                $scope.submission = true; //shows the error message
                $scope.showError= true;
                swal("Error!", "Something went wrong. Please make sure all data was entered correctly and try again.", "error");
            } else {
                swal("Success!", "The user record has been updated. The EMS gods have been made aware of this change. Please be prepared to sacrifice a hot meal.", "success");
                $route.reload();
            }
        });
    };

}]);
