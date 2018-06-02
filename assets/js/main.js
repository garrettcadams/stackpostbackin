function Main(){
    var self= this;
    var DataTable = false;
    var overplay = $(".loading-overplay");
    this.init= function(){
    	//Call Function
        self.optionMain();
        self.actionItem();
        self.actionMultiItem();
        self.actionForm();
        self.enableDatatable();
    };

    this.optionMain = function(){
    	$('[data-toggle="tooltip"]').tooltip({container: "body"}); 

        if($('.box-report').length > 0){
            setTimeout(
                function(){
                    $(".box-report li:first-child .actionItem").trigger("click");
                }, 300
            );

            $(document).on("click", ".box-report li .actionItem", function(){
                $(this).parent().addClass("active").siblings().removeClass("active");
            });
        }

        //Emoji texterea
    	if($('.post-message').length > 0){
	    	el = $(".post-message").emojioneArea({
	            hideSource: true,
	            useSprite: false,
	            pickerPosition    : "bottom",
	            filtersPosition   : "top",
	        });
	    }

        if($('.datetime').length > 0 || $('.date').length > 0){
	        $('.datetime').bootstrapMaterialDatePicker({ format : ' DD/MM/YYYY HH:mm', lang : 'en', weekStart : 1, currentDate: moment().format(' DD/MM/YYYY HH:mm') });
            $('.date').bootstrapMaterialDatePicker({ format : "MMM DD, YYYY", weekStart : 0, time: false, currentDate: moment().format('MMM DD, YYYY') });
        }

	    /*List account*/
	    $(document).on("click", ".list-account .item", function(){
	    	_that = $(this);
	    	if(_that.hasClass("active")){
	    		_that.removeClass("active");
	    		_that.find("input").removeAttr('checked');
	    	}else{
	    		_that.addClass("active");
	    		_that.find("input").attr('checked','checked');
	    	}
	    });

        /*Payment Tabs*/
        $(".payment-tabs .item:first-child a").trigger("click");
        $(document).on("click", ".payment-tabs a", function(){
            $(this).parents(".item").addClass("active").siblings().removeClass("active");
        });

        $(document).on("click", ".payment-plan a", function(){
            $(this).find("input").prop('checked',true);
            $(this).parents(".item").addClass("active").siblings().removeClass("active");
        });

        /*Select search*/
        if($('select.selectpicker').length > 0 || $('.date').length > 0){
            $('select.selectpicker').selectpicker();
        }

        /*Select all*/
        $(document).on("change", ".checkAll", function(){
            _that = $(this);
            if($('input:checkbox').hasClass("checkItem")){
                if(!_that.hasClass("checked")){
                    $('input.checkItem:checkbox').prop('checked',true);
                    _that.addClass('checked');
                }else{
                    $('input.checkItem:checkbox').prop('checked',false);
                    _that.removeClass('checked');        
                }
            }
            return false;
        });

        /*Enable Schedule*/
        $(document).on("change", "#cb-schedule", function(){
            if($("#cb-schedule").is(':checked')){
                $("#schedule-option").removeClass("hide");
            }else{
                $("#schedule-option").addClass("hide");
            }
        });

        /*Ajax Load Modal*/
        $(document).on("click", ".ajaxModal", function(){
            var url = $(this).attr('href');
            $('#mainModal').load(url,function(){
                $('#mainModal').modal({
                    backdrop: 'static',
                    keyboard: false 
                });
                $('#mainModal').modal('show');
            });
            return false;
        });

        /*Schedules*/
        if($(".schedules-list").length > 0){
            _page    = 1;
            _that    = $(".schedules-list");
            _action  = _that.data("action")
            _type    = _that.find("[name='schedule_type']").val();
            _account = _that.find("[name='schedule_account']").val();
            _data    = $.param({token:token, page: 0, type: _type, account: _account});
            self.ajax_post(_that, _action, _data, null);

            $(".schedules-list select[name='schedule_account']").change(function(){
                _that.find(".ajax-sc-list").html('');
                _type    = _that.find("[name='schedule_type']").val();
                _account = _that.find("[name='schedule_account']").val();
                _data    = $.param({token:token, page: 0, type: _type, account: _account});
                self.ajax_post(_that, _action, _data, null);
            }); 

            $(window).scroll(function(){
                _scrollbar_pos = $(window).scrollTop();
                _widown_height = $(document).height() - $(window).height();
                if(_scrollbar_pos >= _widown_height*0.95){

                    _processing = true;
                    if(_processing){
                        _processing = false;
                        
                        _data   = $.param({token:token, page: _page});
                        _return = self.ajax_post(_that, _action, _data, null);
                        if(!_return){

                            _processing = true;
                            _page = _page + 1;
                            $(".schedules-list").attr("data-page", _page);

                        }
                    }

                }

            });
        }
    };

    this.enableDatatable= function(table_full){
        /*Reponsive table*/
        if($('.table-datatable').length > 0 && $(".table_empty").length == 0){
            if(table_full == undefined){
                $('.table-datatable').DataTable({
                    responsive: true,
                    searching: false,
                    paging: false,
                    info: false,
                    scrollX: false,
                    autoWith: false,
                    bSort : false,
                    language: {
                        emptyTable: " ",
                        zeroRecords: " "
                    }
                });
            }else{
                var extensions = {
                    "sFilter": "dataTables_filter right form-group p15 mb0"
                }
                // Used when bJQueryUI is false
                $.extend($.fn.dataTableExt.oStdClasses, extensions);
                // Used when bJQueryUI is true
                $.extend($.fn.dataTableExt.oJUIClasses, extensions);

                $.extend( $.fn.dataTableExt.oStdClasses, {
                    "sFilterInput": "form-control lead mb0"
                });

                DataTable = $('.table-datatable').DataTable({
                    responsive: true,
                    searching: true,
                    paging: false,
                    info: false,
                    scrollX: false,
                    autoWith: false,
                    bSort : true,
                    language: {
                        emptyTable: " ",
                        zeroRecords: " ",
                        search: " "
                    },
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });

                $('.dataTables_filter input').attr("placeholder", enter_keyword_to_search);
            }
        }
    };

    this.actionItem= function(){
        $(document).on('click', ".actionItem", function(event) {
            event.preventDefault();    
            var _that           = $(this);
            var _action         = _that.attr("href");
            var _id             = _that.data("id");
            var _data           = $.param({token:token, id: _id});

            self.ajax_post(_that, _action, _data, null);
            return false;
        });
    };

    this.actionMultiItem= function(){
        $(document).on('click', ".actionMultiItem", function(event) {
            event.preventDefault();    
            var _that           = $(this);
            var _form           = _that.closest("form");
            var _action         = _that.attr("href");
            var _params         = _that.data("params");
            var _data           = _form.serialize();
            var _data           = _data + '&' + $.param({token:token}) + "&" + _params;
            self.ajax_post(_that, _action, _data, null);
            return false;
        });
    };

    this.actionForm= function(){
        $(document).on('submit', ".actionForm", function(event) {
            event.preventDefault();    
            var _that           = $(this);
            var _action         = _that.attr("action");
            var _data           = _that.serialize();
            var _data           = _data + '&' + $.param({token:token});
            
            self.ajax_post(_that, _action, _data, null);
        });
    };

    this.ajax_post = function(_that, _action, _data, _function){
        _confirm        = _that.data("confirm");
        _transfer       = _that.data("transfer");
        _type_message   = _that.data("type-message");
        _rediect        = _that.data("redirect");
        _content        = _that.data("content");
        _append_content = _that.data("append_content");
        _callback       = _that.data("callback");
        _hide_overplay  = _that.data("hide-overplay");
        _type           = _that.data("result");
        _object         = false;
        if(_type == undefined){
            _type = 'json';
        }

        if(_confirm != undefined){
            if(!confirm(_confirm)) return false;
        }

        if(!_that.hasClass("disabled")){
            if(_hide_overplay == undefined || _hide_overplay == 1){
                self.overplay();
            }
            _that.addClass("disabled");
            $.post(_action, _data, function(_result){
                
                //Check is object
                if(typeof _result != 'object'){
                    try {
                        _result = $.parseJSON(_result);
                        _object = true;
                    } catch (e) {
                        _object = false;
                    }
                }else{
                    _object = true;
                }

                //Run function
                if(_function != null){
                    _function.apply(this, [_result]);
                }

                //Callback function
                if(_result.callback != undefined){
                    self.callbacks(_result.callback);
                }

                //Callback
                if(_callback != undefined){
                    var fn = window[_callback];
                    if (typeof fn === "function") fn(_result);
                }

                //Using for update
                if(_transfer != undefined){
                    _that.removeClass("tag-success tag-danger").addClass(_result.tag).text(_result.text);
                }

                //Add content
                if(_content != undefined && _object == false){
                    if(_append_content != undefined){
                        $("."+_content).append(_result);
                    }else{
                        $("."+_content).html(_result);
                    }

                    //Enable DataTable
                    if(_result.search("table-datatable") != -1){
                        self.enableDatatable(true);
                    }
                }

                //Hide Loading
                overplay.hide();
                _that.removeClass("disabled");

                //Redirect
                self.redirect(_rediect, _result.status);

                //Message
                if(_result.status != undefined){
                    switch(_type_message){
                        case "text":
                            self.notify(_result.message, _result.status);
                            break;

                        default:
                            self.notify(_result.message, _result.status);
                            break;
                    }
                }

            }, _type).fail(function() {
                _that.removeClass("disabled");
            });
        }

        return false;
    };

    this.callbacks = function(_function){
        $("body").append(_function);
    };

    this.overplay = function(){
        overplay.show();
        if($(".modal").hasClass("in")){
            overplay.addClass("top");
        }else{
            overplay.removeClass("top");
        }
    };

    this.redirect = function(_rediect, _status){
        if(_rediect != undefined && _status == "success"){
            setTimeout(function(){
                window.location.assign(_rediect);
            }, 1500);
        }
    };

    this.notify = function(_message, _type){
    	switch(_type){
    		case "success":
    			backgroundColor = "#16D39A";
    			break;

    		case "error":
    			backgroundColor = "#FF7588";
    			break;

    		case "default":
    			backgroundColor = "#CCD5DB";
    			break;
    	}

        iziToast.show({
    		theme: 'dark',
    		icon: 'ft-bell',
		    title: '',
            position: 'bottomCenter',
		    message: _message,
		    backgroundColor: backgroundColor,
		    progressBarColor: 'rgb(0, 255, 184)',
		});
    };

    this.statusOverplay = function(_status){
        if(_status == undefined || _status == "show"){
            $(".hide-overplay").addClass("loading-overplay").removeClass("hide-overplay");
        }else{
            $(".loading-overplay").addClass("hide-overplay").removeClass("loading-overplay");
        }
    };

    this.statusCardOverplay = function(_status){
        if(_status == undefined || _status == "show"){
            $(".card-overplay").fadeIn();
        }else{
            $(".card-overplay").fadeOut();
        }
    };

    this.removeParam = function(key, sourceURL) {
        var rtn = "",
            param,
            params_arr = [],
            queryString = sourceURL.split("?")[0];
        if (queryString !== "") {
            params_arr = queryString.split("&");
            for (var i = params_arr.length - 1; i >= 0; i -= 1) {
                param = params_arr[i].split("=")[0];
                if (param === key) {
                    params_arr.splice(i, 1);
                }
            }
            rtn = params_arr.join("&");
        }
        return rtn;
    };
}
Main= new Main();
$(function(){
    Main.init();
});


function executeFunctionByName(functionName, context /*, args */) {
  var args = Array.prototype.slice.call(arguments, 2);
  var namespaces = functionName.split(".");
  var func = namespaces.pop();
  for(var i = 0; i < namespaces.length; i++) {
    context = context[namespaces[i]];
  }
  return context[func].apply(context, args);
}