<input type="hidden" name="method" value="mercadopago">

  <div class="row mt-2">

    <div class="col-lg-6 mb-2">
      <input class="form-control" type="text" placeholder="{{ __('Credit Card Number') }}" id="cardNumber" data-checkout="cardNumber" onselectstart="return false" autocomplete=off required />
    </div>

    <div class="col-lg-6 mb-2">
      <input class="form-control" type="text" id="securityCode" data-checkout="securityCode" placeholder="{{ __('Security Code') }}" onselectstart="return false" autocomplete=off required />
    </div>

    <div class="col-lg-6 mb-2">
      <input class="form-control" type="text" id="cardExpirationMonth" data-checkout="cardExpirationMonth" placeholder="{{ __('Expiration Month') }}" autocomplete=off required />
    </div>

    <div class="col-lg-6 mb-2">
    <input class="form-control" type="text" id="cardExpirationYear" data-checkout="cardExpirationYear" placeholder="{{ __('Expiration Year') }}" autocomplete=off required />
    </div>

    <div class="col-lg-6 mb-2">
      <input class="form-control" type="text" id="cardholderName" data-checkout="cardholderName" placeholder="{{ __('Card Holder Name') }}" required />
    </div>
    <div class="col-lg-6">
      <select class="form-control" id="docType" data-checkout="docType" required>
      </select>
    </div>

    <div class="col-lg-6">
      <input class="form-control" type="text" id="docNumber" data-checkout="docNumber" placeholder="{{ __('Document Number') }}" required />
    </div>

  </div>


    <input type="hidden" id="installments" value="1"/>
    <input type="hidden" name="description"/>
    <input type="hidden" name="paymentMethodId" />

  
    <script>
     
      Mercadopago.setPublishableKey("{{$gs->mercado_publish_key}}");
      
      function getBin() {
          var ccNumber = document.querySelector('input[data-checkout="cardNumber"]');
          return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
      };
      
      function guessingPaymentMethod(event) {
          var bin = getBin();
      
          if (event.type == "keyup") {
              if (bin.length >= 6) {
                  Mercadopago.getPaymentMethod({
                      "bin": bin
                  }, setPaymentMethodInfo);
              }
          } else {
              setTimeout(function() {
                  if (bin.length >= 6) {
                      Mercadopago.getPaymentMethod({
                          "bin": bin
                      }, setPaymentMethodInfo);
                  }
              }, 100);
          }
      };
      
      Mercadopago.getIdentificationTypes();
      
      
      function setPaymentMethodInfo(status, response) {
          if (status == 200) {
              // do somethings ex: show logo of the payment method
              var form = document.querySelector('#mercadopago');
      
              if (document.querySelector("input[name=paymentMethodId]") == null) {
                  var paymentMethod = document.createElement('input');
                  paymentMethod.setAttribute('name', "paymentMethodId");
                  paymentMethod.setAttribute('type', "hidden");
                  paymentMethod.setAttribute('value', response[0].id);
      
                  form.appendChild(paymentMethod);
              } else {
                  document.querySelector("input[name=paymentMethodId]").value = response[0].id;
              }
          }
      };
      
      function addEvent(el, eventName, handler) {
          if (el.addEventListener) {
             el.addEventListener(eventName, handler);
          } else {
              el.attachEvent('on' + eventName, function(){
                handler.call(el);
              });
          }
      };
      
      addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'keyup', guessingPaymentMethod);
      addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'change', guessingPaymentMethod);
      
      doSubmit = false;
      addEvent(document.querySelector('#mercadopago'),'submit',doPay);
      function doPay(event){
          event.preventDefault();
          if(!doSubmit){
              var $form = document.querySelector('#mercadopago');
      
              Mercadopago.createToken($form, sdkResponseHandler); // The function "sdkResponseHandler" is defined below
      
              return false;
          }
      };
      
      function sdkResponseHandler(status, response) {
          console.log(response);
      
          if (status != 200 && status != 201) {
              alert("verify filled data");
          }else{
      
              var form = document.querySelector('#mercadopago');
      
              var card = document.createElement('input');
              card.setAttribute('name',"token");
              card.setAttribute('type',"hidden");
              card.setAttribute('value',response.id);
              form.appendChild(card);
              // doSubmit=true;
               form.submit();
          }
      };
      </script>