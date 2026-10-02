import React, { useState, useEffect } from 'react';
import {
    Users,
    UserPlus,
    Shield,
    CheckCircle2,
    XCircle,
    Clock,
    Lock,
    Key,
    AlertTriangle,
    Mail,
    User,
    Check,
    RefreshCw,
    X
} from 'lucide-react';
import { api } from '../services/api';

export default function TeamView({ user, hasPermission }) {
    const [teamData, setTeamData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [isInviteModalOpen, setIsInviteModalOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [actionMsg, setActionMsg] = useState(null);

    // Form state
    const [inviteForm, setInviteForm] = useState({
        name: '',
        email: '',
        role_id: '',
        access_level: 'operational',
        expires_at: '',
        reason: '',
        password: '',
    });

    useEffect(() => {
        loadTeam();
    }, []);

    async function loadTeam() {
        setLoading(true);
        setError(null);
        try {
            const data = await api.getTeam();
            setTeamData(data);
            if (data.available_roles?.length > 0 && !inviteForm.role_id) {
                setInviteForm(f => ({ ...f, role_id: data.available_roles[0].id }));
            }
        } catch (err) {
            setError(err.message || 'Failed to load team data.');
        } finally {
            setLoading(false);
        }
    }

    async function handleInviteSubmit(e) {
        e.preventDefault();
        setIsSubmitting(true);
        setActionMsg(null);
        try {
            await api.inviteTeamMember(inviteForm);
            setActionMsg({ type: 'success', text: `Access granted to ${inviteForm.name} (${inviteForm.email})` });
            setIsInviteModalOpen(false);
            setInviteForm({
                name: '',
                email: '',
                role_id: teamData?.available_roles?.[0]?.id || '',
                access_level: 'operational',
                expires_at: '',
                reason: '',
                password: '',
            });
            await loadTeam();
        } catch (err) {
            setActionMsg({ type: 'error', text: err.message || 'Failed to assign user access.' });
        } finally {
            setIsSubmitting(false);
        }
    }

    async function handleRoleChange(accessId, newRoleId) {
        try {
            await api.updateTeamMemberRole(accessId, { role_id: newRoleId });
            setActionMsg({ type: 'success', text: 'Role updated successfully.' });
            await loadTeam();
        } catch (err) {
            setActionMsg({ type: 'error', text: err.message || 'Failed to update role.' });
        }
    }

    async function handleRevokeAccess(accessId, name) {
        if (!confirm(`Are you sure you want to revoke farm access for ${name}?`)) return;
        try {
            await api.revokeTeamMember(accessId);
            setActionMsg({ type: 'success', text: `Access revoked for ${name}.` });
            await loadTeam();
        } catch (err) {
            setActionMsg({ type: 'error', text: err.message || 'Failed to revoke access.' });
        }
    }

    const canManageTeam = hasPermission ? (hasPermission('team.manage') || hasPermission('*') || user?.role === 'Farm Owner') : true;

    return (
        <div className="space-y-6">
            {/* Header section */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Users className="w-6 h-6 text-green-600 dark:text-green-400" />
                        Team & User Access Control
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Manage roles, delegate farm permissions, and track active staff credentials for <span className="font-semibold text-slate-800 dark:text-slate-200">{teamData?.farm_name || 'Active Farm'}</span>.
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        onClick={loadTeam}
                        className="btn-ghost flex items-center gap-1.5"
                        title="Refresh Team List"
                    >
                        <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
                        <span>Refresh</span>
                    </button>
                    {canManageTeam && (
                        <button
                            onClick={() => setIsInviteModalOpen(true)}
                            className="btn-primary flex items-center gap-2"
                        >
                            <UserPlus className="w-4 h-4" />
                            <span>Invite Member</span>
                        </button>
                    )}
                </div>
            </div>

            {/* Notification alert */}
            {actionMsg && (
                <div className={`p-4 rounded-xl flex items-center justify-between border ${actionMsg.type === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300'}`}>
                    <div className="flex items-center gap-2.5 text-sm font-medium">
                        {actionMsg.type === 'success' ? <CheckCircle2 className="w-5 h-5 text-emerald-600 dark:text-emerald-400" /> : <AlertTriangle className="w-5 h-5 text-rose-600 dark:text-rose-400" />}
                        <span>{actionMsg.text}</span>
                    </div>
                    <button onClick={() => setActionMsg(null)} className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X className="w-4 h-4" />
                    </button>
                </div>
            )}

            {/* Error display */}
            {error && (
                <div className="card p-6 border-rose-200 dark:border-rose-900 bg-rose-50/50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-300 text-sm">
                    {error}
                </div>
            )}

            {/* Team Roster Grid */}
            <div className="card overflow-hidden">
                <div className="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Shield className="w-4 h-4 text-green-600" />
                        <h2 className="font-semibold text-slate-900 dark:text-white">Active Farm Personnel ({teamData?.team?.length ?? 0})</h2>
                    </div>
                    <span className="text-xs text-slate-500">ISO RBAC Access Control</span>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full data-table">
                        <thead>
                            <tr>
                                <th>Personnel</th>
                                <th>Assigned Role</th>
                                <th>Access Scope</th>
                                <th>Granted By</th>
                                <th>Status / Expiry</th>
                                {canManageTeam && <th className="text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan="6" className="text-center py-8 text-slate-400">
                                        <div className="inline-block w-6 h-6 border-2 border-green-500 border-t-transparent rounded-full animate-spin mb-2" />
                                        <p>Loading farm members…</p>
                                    </td>
                                </tr>
                            ) : teamData?.team?.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="text-center py-8 text-slate-400">
                                        No personnel assigned to this farm yet.
                                    </td>
                                </tr>
                            ) : (
                                teamData?.team?.map(member => {
                                    const isExpired = member.expires_at && new Date(member.expires_at) < new Date();
                                    return (
                                        <tr key={member.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td>
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-300">
                                                        {member.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div className="font-semibold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                                                            {member.name}
                                                            {member.user_id === user?.id && (
                                                                <span className="text-[10px] px-1.5 py-0.5 rounded bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 font-medium">You</span>
                                                            )}
                                                        </div>
                                                        <div className="text-xs text-slate-500 flex items-center gap-1">
                                                            <Mail className="w-3 h-3 text-slate-400" />
                                                            {member.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {canManageTeam && member.user_id !== user?.id ? (
                                                    <select
                                                        value={member.role_id}
                                                        onChange={(e) => handleRoleChange(member.id, e.target.value)}
                                                        className="form-input text-xs py-1 px-2 w-auto font-medium"
                                                    >
                                                        {teamData?.available_roles?.map(r => (
                                                            <option key={r.id} value={r.id}>{r.name}</option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <span className={`badge ${
                                                        member.role_slug === 'farm_owner' ? 'badge-green' :
                                                        member.role_slug === 'herd_manager' ? 'badge-blue' :
                                                        member.role_slug === 'veterinarian' ? 'badge-amber' : 'badge-amber'
                                                    }`}>
                                                        {member.role_name}
                                                    </span>
                                                )}
                                            </td>
                                            <td>
                                                <span className="text-xs font-mono uppercase tracking-wider text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                                    {member.access_level.replace('_', ' ')}
                                                </span>
                                            </td>
                                            <td>
                                                <div className="text-xs text-slate-600 dark:text-slate-400">
                                                    {member.granted_by_name}
                                                </div>
                                                <div className="text-[11px] text-slate-400">
                                                    {member.granted_at ? new Date(member.granted_at).toLocaleDateString() : 'Initial'}
                                                </div>
                                            </td>
                                            <td>
                                                {member.is_active && !isExpired ? (
                                                    <div className="flex flex-col gap-0.5">
                                                        <span className="inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                                            Active
                                                        </span>
                                                        <span className="text-[11px] text-slate-400">
                                                            {member.expires_at ? `Expires ${new Date(member.expires_at).toLocaleDateString()}` : 'Permanent access'}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 text-xs text-rose-600 dark:text-rose-400 font-medium">
                                                        <XCircle className="w-3.5 h-3.5" />
                                                        {isExpired ? 'Expired' : 'Revoked'}
                                                    </span>
                                                )}
                                            </td>
                                            {canManageTeam && (
                                                <td className="text-right">
                                                    {member.user_id !== user?.id && member.is_active && (
                                                        <button
                                                            onClick={() => handleRevokeAccess(member.id, member.name)}
                                                            className="text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-medium px-2 py-1 rounded hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
                                                        >
                                                            Revoke
                                                        </button>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Role & Permissions Directory */}
            <div className="card p-5">
                <h3 className="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <Key className="w-4 h-4 text-green-600" />
                    Role Capabilities & Access Matrix
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {teamData?.available_roles?.map(role => (
                        <div key={role.id} className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="font-bold text-sm text-slate-900 dark:text-white">{role.name}</span>
                                <span className="text-[10px] text-slate-500 uppercase tracking-wider">{role.slug}</span>
                            </div>
                            <p className="text-xs text-slate-500 leading-relaxed min-h-[36px]">
                                {role.description || 'Full permissions across all modules.'}
                            </p>
                            <div className="pt-2 border-t border-slate-200 dark:border-slate-700/60">
                                <span className="text-[11px] font-semibold text-slate-700 dark:text-slate-300 block mb-1.5">
                                    Permissions ({role.permissions?.length ?? 0}):
                                </span>
                                <div className="flex flex-wrap gap-1">
                                    {role.permissions?.map(p => (
                                        <span key={p} className="text-[10px] px-1.5 py-0.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                            {p}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Invite Modal */}
            {isInviteModalOpen && (
                <div className="modal-backdrop">
                    <div className="modal-box">
                        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <h3 className="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <UserPlus className="w-5 h-5 text-green-600" />
                                Invite Farm Team Member
                            </h3>
                            <button
                                onClick={() => setIsInviteModalOpen(false)}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleInviteSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Full Name *
                                </label>
                                <input
                                    type="text"
                                    required
                                    placeholder="e.g. Dr. Salman Khan"
                                    value={inviteForm.name}
                                    onChange={(e) => setInviteForm(f => ({ ...f, name: e.target.value }))}
                                    className="form-input"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Email Address *
                                </label>
                                <input
                                    type="email"
                                    required
                                    placeholder="e.g. salman@vetcare.pk"
                                    value={inviteForm.email}
                                    onChange={(e) => setInviteForm(f => ({ ...f, email: e.target.value }))}
                                    className="form-input"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Assigned Role *
                                    </label>
                                    <select
                                        value={inviteForm.role_id}
                                        onChange={(e) => setInviteForm(f => ({ ...f, role_id: e.target.value }))}
                                        className="form-input"
                                    >
                                        {teamData?.available_roles?.map(r => (
                                            <option key={r.id} value={r.id}>{r.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Access Level
                                    </label>
                                    <select
                                        value={inviteForm.access_level}
                                        onChange={(e) => setInviteForm(f => ({ ...f, access_level: e.target.value }))}
                                        className="form-input"
                                    >
                                        <option value="operational">Operational</option>
                                        <option value="veterinary_only">Veterinary Only</option>
                                        <option value="read_only">Read-Only / Auditor</option>
                                        <option value="full">Full Administration</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Expiration Date (Optional)
                                    </label>
                                    <input
                                        type="date"
                                        value={inviteForm.expires_at}
                                        onChange={(e) => setInviteForm(f => ({ ...f, expires_at: e.target.value }))}
                                        className="form-input"
                                    />
                                    <span className="text-[11px] text-slate-400 mt-0.5 block">Leave empty for permanent staff</span>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                        Temporary Password
                                    </label>
                                    <input
                                        type="password"
                                        placeholder="Min 6 characters (optional)"
                                        value={inviteForm.password}
                                        onChange={(e) => setInviteForm(f => ({ ...f, password: e.target.value }))}
                                        className="form-input"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Delegation Note / Retainer Scope
                                </label>
                                <textarea
                                    rows="2"
                                    placeholder="e.g. Annual Veterinary Retainer or Night-shift Milking Lead"
                                    value={inviteForm.reason}
                                    onChange={(e) => setInviteForm(f => ({ ...f, reason: e.target.value }))}
                                    className="form-input"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                                <button
                                    type="button"
                                    onClick={() => setIsInviteModalOpen(false)}
                                    className="btn-ghost"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting}
                                    className="btn-primary"
                                >
                                    {isSubmitting ? (
                                        <>
                                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                            <span>Granting Access…</span>
                                        </>
                                    ) : (
                                        <>
                                            <Check className="w-4 h-4" />
                                            <span>Grant Farm Access</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
