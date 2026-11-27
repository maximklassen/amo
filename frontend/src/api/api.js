const BASE_URL = import.meta.env.VITE_API_URL ?? '';

export class ApiError extends Error{
    constructor(message, status) {
        super(message);
        this.status = status;
    }
}

async function request(endpoint, {body, headers, ...rest} = {}) {
    const response = await fetch(BASE_URL + endpoint, {
        ...rest,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': sessionStorage.getItem('csrf') ?? '',
            ...headers,
        },
        credentials: 'include',
        body: body ? JSON.stringify(body) : undefined,
    });

    let data;
    try {
        data = await response.json();
    }
    catch (error) {
        throw new ApiError('Serverfehler', response.status);
    }

    if (data.error) {
        throw new ApiError(data.message, data.statuscode);
    }
    
    return data.response;
}

// Hilfsfunktion: { status: 'frei', full: true } -> "?status=frei&full=true"
// Leere Werte (undefined, null, '') werden weggelassen.
function query(params = {}) {
    const filtered = Object.entries(params).filter(
        ([, value]) => value !== undefined && value !== null && value !== ''
    );
    const search = new URLSearchParams(filtered).toString();
    return search ? '?' + search : '';
}

const get = (path) => request(path, {method: 'GET'});
const post = (path, body) => request(path, {method: 'POST', body});
const put = (path, body) => request(path, {method: 'PUT', body});
const del = (path) => request(path, {method: 'DELETE'});


//Auth

export async function me() {
    const data = await get('auth/me');
    sessionStorage.setItem('csrf', data.csrf_token);
    return data.user;
}

export async function register(userData) {
    const data = await post('auth/register', userData);
    sessionStorage.setItem('csrf', data.csrf_token);
    return data.user;
}

export async function login(userData) {
    const data = await post('auth/login', userData);
    sessionStorage.setItem('csrf', data.csrf_token);
    return data.user;
}

export async function logout() {
    await post('auth/logout');
    sessionStorage.removeItem('csrf');
    await me();
}

//Profil

export const getProfile = () => get('profile');
export const updateProfile = (profileData) => put('profile', profileData);
export const changePassword = (oldPassword, newPassword) =>
    put('profile/password', { old_password: oldPassword, new_password: newPassword });

//Parzellen

// params: { status: 'frei', full: true }
export const getParcels = (params) => get('parcels' + query(params));
export const getParcel = (id, params) => get(`parcels/${id}` + query(params));
export const createParcel = (parcelData) => post('parcels', parcelData);
export const updateParcel = (id, parcelData) => put(`parcels/${id}`, parcelData);
export const deleteParcel = (id) => del(`parcels/${id}`);
export const releaseParcel = (id) => post(`parcels/${id}/release`);

//Pachtverträge

export const getMyLeases = () => get('leases/my');
// params: { parcel_id: 12, user_id: 5, active: true }
export const getLeases = (params) => get('leases' + query(params));
export const createLease = (leaseData) => post('leases', leaseData);
export const terminateLease = (id, endDate) => put(`leases/${id}/terminate`, { end_date: endDate });

//Mitglieder

// params: { role: 'bewerber' }
export const getUsers = (params) => get('users' + query(params));
export const getUser = (id) => get(`users/${id}`);
export const changeUserRole = (id, role) => put(`users/${id}/role`, { role });
export const deleteUser = (id) => del(`users/${id}`);

//Zähler


export const getMyMeters = () => get('mein-garten/zaehler');
export const addMeterReading = (meterId, value) =>
    post(`mein-garten/zaehler/${meterId}/readings`, { value });

export const getInvoice = (invoiceId) => get(`mein-garten/rechnungen/${invoiceId}`);
export const getInvoices = () => get('mein-garten/rechnungen');