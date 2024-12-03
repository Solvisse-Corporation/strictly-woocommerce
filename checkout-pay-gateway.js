Copyright (C) 2025 Solvisse Corporation

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

const settings = window.wc.wcSettings.getSetting( 'paywithzero_gateway_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title) || window.wp.i18n.__( 'Pay With Zero Gateway', 'paywithzero_gateway');
var collectjs_response = 0;
var resolve_callback;
var fields_callback = false;
const images = 
[
  { src: "https://cdn.paywithzero.net/assets/cards/visa.svg", alt: 'visa' },
  { src: "https://cdn.paywithzero.net/assets/cards/discover.svg", alt: 'discover' },
  { src: "https://cdn.paywithzero.net/assets/cards/mastercard.svg", alt: 'mastercard' },
  { src: "https://cdn.paywithzero.net/assets/cards/amex.svg", alt: 'amex' }
];

const imageElements = images.map((image, index) =>
  React.createElement('li', { key: index },
  React.createElement('img', { src: image.src, alt: image.alt }))
); 

const Content = (props) => {
const {eventRegistration, emitResponse} = props;
const {onPaymentSetup, onCheckoutFail, onCheckoutSuccess} = eventRegistration;

const [showDiv, setShowDiv] = React.useState(false);
const [textButton, setTextButton] = React.useState('Validate');

const handleCheckboxChange = () => 
{
  setShowDiv((prevState) => !prevState);
};

const paymentRequest = () =>
{
  return new Promise((resolve, reject) => 
  {
    if(CollectJS === 'undefined' || !fields_callback) resolve(collectjs_response);
	else if(collectjs_response) resolve(collectjs_response);
	else
	{
	  resolve_callback = resolve;
	  CollectJS.startPaymentRequest();
	}
  });
};
  
React.useEffect(() => 
{
  const handleWindowLoad = () =>
  {
      CollectJS.configure(
     {
      fields: 
      {
        ccnumber: 
	    {
          selector: "#paywithzero_gateway_card_number",
          title: "Card Number",
          placeholder: "0000 0000 0000 0000"
        },
        ccexp: 
	    {
	      selector: "#paywithzero_gateway_card_expiry",
          title: "Card Expiration",
          placeholder: "00 / 0000"
        },
	    cvv: 
	    {
          display: "show",
          selector: "#paywithzero_gateway_card_ccv",
          title: "CVV Code",
          placeholder: "000"
        }
      },
	  fieldsAvailableCallback: function ()
	  {
	    fields_callback = true;
	  },
	  timeoutCallback: function () 
	  {
		collectjs_response = 0;
		resolve_callback(collectjs_response);
	  },
      timeoutDuration: 5000,
      "currency": "USD",
      "variant": "inline",
      "callback": function (response)
      {
        collectjs_response = response;
		resolve_callback(collectjs_response);		
      }
    });	
	document.getElementsByClassName("wc-block-components-radio-control")[0].style = "border:none !important";
  };
  handleWindowLoad();
},[]);

React.useEffect(() => 
{
  const onsubscribe = onPaymentSetup(async() => 
  {
    var result = await paymentRequest();
    if(result == 0) 
	{
	  const failureResponse = 
	  {
	    type: "failure",
		message: "Check payment card information"
      };
	  return failureResponse
	}
	
	const successResponse = 
	{
	  type: "success",
	  meta:
	  {
		paymentMethodData : 
		{
		  paymentToken: result.token,
		  cardNumber: result.card.number
		}
	  }
	};		
	return successResponse;
  });
	
  return () => 
  {
    onsubscribe();
  };
}, 
[
  onPaymentSetup
]);

React.useEffect(() => 
{
  const onsubscribe = onCheckoutFail(async(data) => 
  {	
    console.debug(data);
	
    if(data.processingResponse.paymentDetails.result == "failure")
	{
		document.getElementById("paywithzero_gateway_card_accept_surcharge").style = "";
		document.getElementById("paywithzero_gateway_card_accept_surcharge_text").innerText = data.processingResponse.paymentDetails.surcharge;
	}
	const failureResponse = 
	{
		type: "failure",
		message: "Check payment card information"
      };
	return failureResponse;
  });
	
  return () => 
  {
    onsubscribe();
  };
}, 
[
  onCheckoutFail
]);

return React.createElement('div', null,
React.createElement('div', {style:{display:'flex'}},
React.createElement('span', {style:{display:'flex'}}, window.wp.htmlEntities.decodeEntities( settings.description || '' )),
React.createElement('ul', {style:{display:'flex',listStyle:'none', padding: 0, margin: 0, marginLeft:20}}, imageElements 
)),
  React.createElement("div",
  { style: 
    { 
	  display: "flex",
	  listStyle: "none"
	} 
  }, 
  "" + window.wp.htmlEntities.decodeEntities( settings.surcharge) + ""
  ),
  React.createElement("div",
  { style: 
    { 
	  display: "flex",
	  listStyle: "none",
	  gap: 10
	} 
  },
  React.createElement("div",
  {
    className: "wc-block-components-text-input",
    id: "paywithzero_gateway_card_number",
	style: 
    { 
	  width: "70%"
	}
  },
  "Card Number"
  ),
  React.createElement("div",
  {
    className: "wc-block-components-text-input",
    id: "paywithzero_gateway_card_expiry",
    style: 
    { 
	  width: "40%"
	}
  },
  "Card Expiration"
  ),
  React.createElement("div",
  {
    className: "wc-block-components-text-input",
    id: "paywithzero_gateway_card_ccv",
    style: 
    { 
	  width: "40%"
	}
  },
  "CVC"
  )
  )
);
};
 
const Block_Gateway =
{
  name: 'paywithzero_gateway',
  label: label,
  content: Object( window.wp.element.createElement )(Content, null ),
  edit: Object( window.wp.element.createElement )(Content, null ),
  canMakePayment: function (arg) { return true;},
  ariaLabel: label,
  supports: 
  {
    features: settings.supports
  }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
