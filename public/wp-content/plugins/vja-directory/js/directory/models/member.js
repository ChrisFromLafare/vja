// VjaDirectory.Models.Member = Backbone.Model.extend({
//     defaults: {
//         firstname: '',
//         lastname: '',
//         email: '',
//         phone:'',
//         sport: '',
//         birthdate: '',
//         addr: '',
//         zipcode: '',
//         city: '',
//         sex: ''
//     }
// })

/**
 * Single Member model.
 */

VjaJS.Member = VjaJS.BaseModel.extend( {
    action: '',
    defaults: {
        firstname: '',
        lastname: '',
        email: '',
        phone:'',
        sport: '',
        birthdate: '',
        addr: '',
        zipcode: '',
        city: '',
        sex: ''
    }
} );

