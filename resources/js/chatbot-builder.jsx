import React, { useState, useCallback, useEffect, useRef, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import {
    ReactFlow,
    ReactFlowProvider,
    addEdge,
    useNodesState,
    useEdgesState,
    Controls,
    MiniMap,
    Background,
    BackgroundVariant,
    Handle,
    Position,
    MarkerType,
    useReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

// ── Constantes ────────────────────────────────────────────────────────────────

const BLUE        = '#2563eb';
const BLUE_LIGHT  = '#eff6ff';
const BLUE_BORDER = '#bfdbfe';

// Bootstrap Icons (bi bi-*) — FontAwesome não está instalado no projeto
const NODE_TYPES_CONFIG = {
    message:   { label: 'Mensagem', icon: 'chat-dots'       },
    input:     { label: 'Pergunta', icon: 'keyboard'         },
    condition: { label: 'Condição', icon: 'diagram-2'        },
    action:    { label: 'Ação',     icon: 'lightning-charge' },
    end:       { label: 'Fim',      icon: 'stop-circle'      },
};

const ACTION_TYPES = [
    { value: 'change_stage',       label: 'Trocar etapa do funil'  },
    { value: 'add_tag',            label: 'Adicionar tag'           },
    { value: 'remove_tag',         label: 'Remover tag'             },
    { value: 'assign_human',       label: 'Transferir para humano'  },
    { value: 'close_conversation', label: 'Fechar conversa'         },
    { value: 'save_variable',      label: 'Salvar variável'         },
    { value: 'send_webhook',       label: 'Enviar Webhook (HTTP)'   },
];

const SYSTEM_VARS = [
    '$lead_exists', '$lead_stage_name', '$lead_stage_id', '$lead_source',
    '$lead_tags', '$conversations_count', '$is_returning_contact',
    '$messages_count', '$contact_phone', '$contact_name',
];

const OPERATORS = [
    { value: 'equals',      label: 'igual a'      },
    { value: 'not_equals',  label: 'diferente de' },
    { value: 'contains',    label: 'contém'       },
    { value: 'starts_with', label: 'começa com'   },
    { value: 'ends_with',   label: 'termina com'  },
    { value: 'gt',          label: 'maior que'    },
    { value: 'lt',          label: 'menor que'    },
];

let _idCounter = 1000;
const genId = () => `node-${++_idCounter}`;

// ── Estilos ───────────────────────────────────────────────────────────────────

const field = {
    label: {
        display: 'block', fontSize: 11, fontWeight: 700,
        color: '#6b7280', textTransform: 'uppercase', letterSpacing: '0.06em',
        marginBottom: 5, fontFamily: "'Inter', system-ui, sans-serif",
    },
    input: {
        width: '100%', padding: '7px 10px',
        border: '1px solid #e5e7eb', borderRadius: 7,
        fontSize: 13, color: '#111827', boxSizing: 'border-box',
        background: '#fff', fontFamily: "'Inter', system-ui, sans-serif",
    },
    smallBtn: {
        background: '#f9fafb', border: '1px solid #e5e7eb', color: '#374151',
        borderRadius: 6, padding: '4px 10px', cursor: 'pointer', fontSize: 12, fontWeight: 600,
        fontFamily: "'Inter', system-ui, sans-serif",
    },
};

// ── Base Node ─────────────────────────────────────────────────────────────────

/**
 * rightHandles: array of { id, label }
 * hasDefaultHandle: if true, renders a single centered default source at right
 *
 * Branch handles are rendered inside their own rows (natural document flow)
 * so the node grows vertically as branches are added.
 * React Flow reads handle positions via getBoundingClientRect — works correctly
 * with position:relative rows.
 */
function BaseNode({ type, data, selected, children, rightHandles = [], hasDefaultHandle = true }) {
    const cfg = NODE_TYPES_CONFIG[type] || NODE_TYPES_CONFIG.message;

    const FONT = "'Inter', system-ui, sans-serif";

    return (
        <div style={{
            background: '#fff',
            border: `1.5px solid ${selected ? BLUE : '#e5e7eb'}`,
            borderRadius: 10,
            width: 240,
            position: 'relative',
            boxShadow: selected
                ? `0 0 0 3px ${BLUE}22, 0 6px 20px rgba(0,0,0,0.10)`
                : '0 1px 6px rgba(0,0,0,0.07)',
            fontFamily: FONT,
        }}>

            {/* INÍCIO badge — aparece no nó raiz (sem incoming edges) */}
            {data._isStart && (
                <div style={{
                    position: 'absolute', top: -28, left: '50%', transform: 'translateX(-50%)',
                    background: '#10b981', color: '#fff', fontSize: 10, fontWeight: 700,
                    padding: '3px 10px', borderRadius: 99, whiteSpace: 'nowrap',
                    display: 'flex', alignItems: 'center', gap: 4,
                    boxShadow: '0 2px 6px rgba(16,185,129,0.35)', pointerEvents: 'none',
                    letterSpacing: '0.04em',
                }}>
                    <i className="bi bi-play-fill" style={{ fontSize: 8 }} />
                    INÍCIO DO FLUXO
                </div>
            )}

            {/* LEFT: single target handle — centered on full node height */}
            <Handle
                type="target"
                position={Position.Left}
                style={{
                    background: '#fff',
                    width: 12, height: 12,
                    border: `2.5px solid ${BLUE}`,
                    left: -7,
                    top: '50%',
                    transform: 'translateY(-50%)',
                }}
            />

            {/* Blue header */}
            <div style={{
                background: BLUE,
                borderRadius: '8px 8px 0 0',
                padding: '6px 8px 6px 10px',
                display: 'flex', alignItems: 'center', gap: 6,
                color: '#fff',
                fontFamily: FONT,
            }}>
                <i className={`bi bi-${cfg.icon}`} style={{ fontSize: 10, opacity: 0.85 }} />
                <span style={{ fontSize: 10, fontWeight: 600, letterSpacing: '0.02em', fontFamily: FONT, opacity: 0.9 }}>
                    {cfg.label}
                </span>
                {/* Pencil edit affordance */}
                <div style={{
                    marginLeft: 'auto',
                    width: 18, height: 18,
                    background: 'rgba(255,255,255,0.18)',
                    borderRadius: 5,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    flexShrink: 0,
                    cursor: 'pointer',
                }}>
                    <i className="bi bi-pencil" style={{ fontSize: 9, color: '#fff' }} />
                </div>
            </div>

            {/* Body */}
            <div style={{
                padding: '9px 12px',
                fontSize: 12,
                color: '#374151',
                lineHeight: 1.5,
                minHeight: 32,
                fontFamily: FONT,
            }}>
                {children}
            </div>

            {/* Single default source handle (message, action nodes) */}
            {hasDefaultHandle && type !== 'end' && (
                <Handle
                    type="source"
                    position={Position.Right}
                    id="default"
                    style={{
                        background: BLUE,
                        width: 12, height: 12,
                        border: '2px solid #fff',
                        right: -7,
                        top: '50%',
                        transform: 'translateY(-50%)',
                    }}
                />
            )}

            {/* Branch rows (input / condition nodes)
                Each branch is a natural-flow row; the handle sits at the
                right edge of its row. Node height grows automatically. */}
            {rightHandles.length > 0 && (
                <div style={{ borderTop: '1px solid #f0f2f7' }}>
                    {rightHandles.map((h, i) => (
                        <div
                            key={h.id}
                            style={{
                                position: 'relative',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'flex-end',
                                padding: '7px 26px 7px 12px',
                                borderBottom: i < rightHandles.length - 1 ? '1px solid #f5f6f8' : 'none',
                                overflow: 'visible',
                            }}
                        >
                            <span style={{
                                fontSize: 11, fontWeight: 400,
                                color: '#6b7280',
                                background: '#f3f4f6',
                                padding: '2px 8px',
                                borderRadius: 99,
                                maxWidth: 160,
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                                whiteSpace: 'nowrap',
                                fontFamily: FONT,
                            }}>
                                {h.label}
                            </span>
                            <Handle
                                type="source"
                                position={Position.Right}
                                id={h.id}
                                style={{
                                    background: BLUE,
                                    width: 12, height: 12,
                                    border: '2px solid #fff',
                                    position: 'absolute',
                                    right: -7,
                                    top: '50%',
                                    transform: 'translateY(-50%)',
                                }}
                            />
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// ── Preview helper ────────────────────────────────────────────────────────────

function Preview({ text }) {
    if (!text) return null;
    const trimmed = text.length > 90 ? text.substring(0, 90) + '…' : text;
    return <span style={{ color: '#374151' }}>{trimmed}</span>;
}

function Tag({ children }) {
    return (
        <span style={{
            display: 'inline-block',
            background: BLUE_LIGHT, color: BLUE,
            border: `1px solid ${BLUE_BORDER}`,
            borderRadius: 20, fontSize: 10, fontWeight: 600,
            padding: '1px 7px', marginTop: 5,
        }}>
            {children}
        </span>
    );
}

// ── Node Components ───────────────────────────────────────────────────────────

function MessageNode({ id, data, selected }) {
    return (
        <BaseNode type="message" data={data} selected={selected} hasDefaultHandle={true}>
            {data.image_url && (
                <div style={{
                    marginBottom: 6, borderRadius: 6, overflow: 'hidden',
                    background: '#f3f4f6', border: '1px solid #e5e7eb',
                }}>
                    <img
                        src={data.image_url}
                        alt="preview"
                        style={{ width: '100%', height: 60, objectFit: 'cover', display: 'block' }}
                        onError={e => {
                            e.target.style.display = 'none';
                            e.target.parentNode.style.display = 'none';
                        }}
                    />
                </div>
            )}
            <Preview text={data.text} />
        </BaseNode>
    );
}

function InputNode({ id, data, selected }) {
    const branches = data.branches || [];
    // Right handles: one per branch + default at the end
    const rightHandles = [
        ...branches.map((b, i) => ({ id: b.handle || `branch-${i}`, label: b.label || `Branch ${i + 1}` })),
        { id: 'default', label: 'Padrão' },
    ];
    return (
        <BaseNode type="input" data={data} selected={selected} hasDefaultHandle={false} rightHandles={rightHandles}>
            <Preview text={data.text} />
            {data.save_to && <Tag><i className="bi bi-floppy" style={{ marginRight: 4, fontSize: 9 }} />{data.save_to}</Tag>}
        </BaseNode>
    );
}

function ConditionNode({ id, data, selected }) {
    const conditions = data.conditions || [];
    const rightHandles = [
        ...conditions.map((c, i) => ({ id: c.handle || `branch-${i}`, label: c.label || `Cond ${i + 1}` })),
        { id: 'default', label: '↩ Padrão' },
    ];
    return (
        <BaseNode type="condition" data={data} selected={selected} hasDefaultHandle={false} rightHandles={rightHandles}>
            {data.variable && (
                <Tag><i className="bi bi-code-slash" style={{ marginRight: 4, fontSize: 9 }} />{'{{' + data.variable + '}}'}</Tag>
            )}
        </BaseNode>
    );
}

function ActionNode({ id, data, selected }) {
    const actionType = ACTION_TYPES.find(a => a.value === data.type);
    return (
        <BaseNode type="action" data={data} selected={selected} hasDefaultHandle={true}>
            {actionType && (
                <span style={{ fontWeight: 500, fontFamily: "'Inter', sans-serif", fontSize: 9, marginRight: 10 }}>{actionType.label}</span>
            )}
            {data.type === 'change_stage' && data.stage_name && (
                <Tag><i className="bi bi-arrow-right" style={{ marginRight: 4, fontSize: 9 }} />{data.stage_name}</Tag>
            )}
            {(data.type === 'add_tag' || data.type === 'remove_tag') && data.value && (
                <Tag><i className="bi bi-tag" style={{ marginRight: 4, fontSize: 9 }} />{data.value}</Tag>
            )}
            {data.type === 'send_webhook' && data.url && (
                <div style={{ fontSize: 10, color: '#9ca3af', marginTop: 4, wordBreak: 'break-all' }}>
                    {data.method || 'POST'} {data.url.substring(0, 36)}{data.url.length > 36 ? '…' : ''}
                </div>
            )}
        </BaseNode>
    );
}

function EndNode({ id, data, selected }) {
    return (
        <BaseNode type="end" data={data} selected={selected} hasDefaultHandle={false} rightHandles={[]}>
            <Preview text={data.text} />
        </BaseNode>
    );
}

const nodeTypes = { message: MessageNode, input: InputNode, condition: ConditionNode, action: ActionNode, end: EndNode };

// ── Node Edit Panel ───────────────────────────────────────────────────────────

function FieldGroup({ label, children }) {
    return (
        <div style={{ marginBottom: 14 }}>
            <label style={field.label}>{label}</label>
            {children}
        </div>
    );
}

function MessageForm({ data, update, textareaRef, saveCursor }) {
    const FONT      = "'Inter', system-ui, sans-serif";
    const hasImage  = data.image_url != null;
    const uploadRef = useRef(null);
    const [uploading, setUploading] = useState(false);
    const [uploadErr, setUploadErr] = useState('');

    const handleFileUpload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setUploading(true);
        setUploadErr('');
        try {
            const form = new FormData();
            form.append('image', file);
            const res  = await fetch(window.chatbotBuilderData.uploadUrl, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': window.chatbotBuilderData.csrfToken },
                body:    form,
            });
            const json = await res.json();
            if (json.url) {
                update('image_url', json.url);
            } else {
                setUploadErr('Erro ao enviar imagem.');
            }
        } catch {
            setUploadErr('Erro de conexão.');
        } finally {
            setUploading(false);
            e.target.value = '';
        }
    };

    // Wrap selected text with WhatsApp format marker.
    // onMouseDown={e.preventDefault()} on each button keeps the textarea focused
    // so selectionStart/End remain valid when the handler runs.
    const applyFormat = (marker) => {
        const ta = textareaRef.current;
        if (!ta) return;
        const start  = ta.selectionStart;
        const end    = ta.selectionEnd;
        const text   = data.text || '';
        const newVal = text.substring(0, start) + marker + text.substring(start, end) + marker + text.substring(end);
        update('text', newVal);
        requestAnimationFrame(() => {
            ta.focus();
            ta.selectionStart = start + marker.length;
            ta.selectionEnd   = end   + marker.length;
        });
    };

    // Render WhatsApp markdown as HTML for the preview pane.
    const renderPreview = (text) => {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*([^*\n]+)\*/g,  '<strong>$1</strong>')
            .replace(/_([^_\n]+)_/g,    '<em>$1</em>')
            .replace(/~([^~\n]+)~/g,    '<s>$1</s>')
            .replace(/`([^`\n]+)`/g,    '<code style="background:#f3f4f6;padding:0 3px;border-radius:3px;font-size:11px;font-family:monospace">$1</code>')
            .replace(/\{\{(\$?[\w]+)\}\}/g, '<span style="background:#eff6ff;color:#2563eb;padding:0 3px;border-radius:3px;font-size:10px;font-family:monospace">{{$1}}</span>')
            .replace(/\n/g, '<br>');
    };

    return (
        <>
            {/* ── Image section ── */}
            <div style={{ marginBottom: 14 }}>
                <label style={field.label}>Mídia</label>

                {!hasImage ? (
                    /* Buttons to add image */
                    <div style={{ display: 'flex', gap: 6 }}>
                        {/* Upload from device */}
                        <input
                            ref={uploadRef}
                            type="file"
                            accept="image/*"
                            style={{ display: 'none' }}
                            onChange={handleFileUpload}
                        />
                        <button
                            onMouseDown={e => e.preventDefault()}
                            onClick={() => uploadRef.current?.click()}
                            disabled={uploading}
                            style={{
                                ...field.smallBtn,
                                display: 'inline-flex', alignItems: 'center', gap: 5,
                                opacity: uploading ? 0.6 : 1,
                            }}
                        >
                            <i className={`bi bi-${uploading ? 'hourglass-split' : 'upload'}`} style={{ fontSize: 11 }} />
                            {uploading ? 'Enviando…' : 'Upload'}
                        </button>
                        {/* Or add by URL */}
                        <button
                            onMouseDown={e => e.preventDefault()}
                            onClick={() => update('image_url', '')}
                            style={{ ...field.smallBtn, display: 'inline-flex', alignItems: 'center', gap: 5 }}
                        >
                            <i className="bi bi-link-45deg" style={{ fontSize: 11 }} />
                            URL
                        </button>
                    </div>
                ) : (
                    /* Image preview + controls */
                    <div style={{ border: '1px solid #e5e7eb', borderRadius: 9, overflow: 'hidden' }}>
                        {/* Thumbnail */}
                        {data.image_url && (
                            <div style={{ background: '#f3f4f6', position: 'relative' }}>
                                <img
                                    src={data.image_url}
                                    alt="preview"
                                    style={{
                                        width: '100%', maxHeight: 120,
                                        objectFit: 'cover', display: 'block',
                                    }}
                                    onError={e => { e.target.style.display = 'none'; }}
                                />
                            </div>
                        )}
                        {/* URL input */}
                        <div style={{ padding: '8px 10px', borderTop: data.image_url ? '1px solid #e5e7eb' : 'none', background: '#fff' }}>
                            <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                                <input
                                    style={{ ...field.input, flex: 1, fontSize: 12 }}
                                    value={data.image_url || ''}
                                    onChange={e => update('image_url', e.target.value)}
                                    placeholder="https://exemplo.com/imagem.jpg"
                                />
                                {/* Re-upload */}
                                <input
                                    ref={uploadRef}
                                    type="file"
                                    accept="image/*"
                                    style={{ display: 'none' }}
                                    onChange={handleFileUpload}
                                />
                                <button
                                    onMouseDown={e => e.preventDefault()}
                                    onClick={() => uploadRef.current?.click()}
                                    disabled={uploading}
                                    title="Trocar imagem"
                                    style={{ ...field.smallBtn, padding: '4px 8px', display: 'inline-flex', alignItems: 'center', opacity: uploading ? 0.6 : 1 }}
                                >
                                    <i className={`bi bi-${uploading ? 'hourglass-split' : 'arrow-repeat'}`} style={{ fontSize: 12 }} />
                                </button>
                                {/* Remove */}
                                <button
                                    onMouseDown={e => e.preventDefault()}
                                    onClick={() => update('image_url', null)}
                                    title="Remover imagem"
                                    style={{ ...field.smallBtn, padding: '4px 8px', display: 'inline-flex', alignItems: 'center', background: '#fff0f0', color: '#dc2626', border: '1px solid #fca5a5' }}
                                >
                                    <i className="bi bi-trash3" style={{ fontSize: 12 }} />
                                </button>
                            </div>
                            {uploadErr && (
                                <p style={{ fontSize: 11, color: '#dc2626', margin: '4px 0 0', fontFamily: FONT }}>{uploadErr}</p>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* ── Text + toolbar ── */}
            <div style={{ marginBottom: 14 }}>
                <label style={field.label}>Texto da mensagem</label>

                {/* Formatting toolbar */}
                <div style={{
                    display: 'flex', alignItems: 'center', gap: 2,
                    padding: '4px 8px',
                    background: '#f9fafb',
                    border: '1px solid #e5e7eb',
                    borderBottom: 'none',
                    borderRadius: '7px 7px 0 0',
                }}>
                    {[
                        { marker: '*',  icon: 'type-bold',          title: 'Negrito (*texto*)'      },
                        { marker: '_',  icon: 'type-italic',        title: 'Itálico (_texto_)'      },
                        { marker: '~',  icon: 'type-strikethrough', title: 'Tachado (~texto~)'      },
                        { marker: '`',  icon: 'code',               title: 'Mono (` texto `)'       },
                    ].map(({ marker, icon, title }) => (
                        <button
                            key={marker}
                            onMouseDown={e => e.preventDefault()}
                            onClick={() => applyFormat(marker)}
                            title={title}
                            style={{
                                background: 'none', border: '1px solid transparent',
                                borderRadius: 4, padding: '3px 7px',
                                cursor: 'pointer', color: '#374151',
                                display: 'flex', alignItems: 'center',
                                transition: 'background .1s',
                            }}
                            onMouseEnter={e => { e.currentTarget.style.background = '#e5e7eb'; }}
                            onMouseLeave={e => { e.currentTarget.style.background = 'none'; }}
                        >
                            <i className={`bi bi-${icon}`} style={{ fontSize: 12 }} />
                        </button>
                    ))}
                    <div style={{ flex: 1 }} />
                    <span style={{ fontSize: 9, color: '#9ca3af', fontFamily: FONT }}>WhatsApp</span>
                </div>

                <textarea
                    ref={textareaRef}
                    data-field="text"
                    style={{ ...field.input, height: 100, resize: 'vertical', borderRadius: '0 0 7px 7px' }}
                    value={data.text || ''}
                    onChange={e => update('text', e.target.value)}
                    onKeyUp={saveCursor('text')}
                    onClick={saveCursor('text')}
                    onBlur={saveCursor('text')}
                    placeholder="Olá, {{$contact_name}}! Como posso ajudar?"
                />
            </div>

            {/* ── Live preview ── */}
            {(data.text || (hasImage && data.image_url)) && (
                <div style={{ marginBottom: 14 }}>
                    <label style={{ ...field.label, marginBottom: 5 }}>Pré-visualização</label>
                    <div style={{
                        background: '#f0fdf4', border: '1px solid #bbf7d0',
                        borderRadius: 8, padding: '9px 12px',
                        fontSize: 13, color: '#111827',
                        lineHeight: 1.55, fontFamily: FONT,
                        maxHeight: 130, overflowY: 'auto',
                    }}>
                        {hasImage && data.image_url && (
                            <div style={{
                                display: 'flex', alignItems: 'center', gap: 5,
                                marginBottom: 7, fontSize: 11, color: '#6b7280',
                                background: '#fff', padding: '3px 8px',
                                borderRadius: 5, border: '1px solid #e5e7eb',
                            }}>
                                <i className="bi bi-image" style={{ color: '#059669', fontSize: 12 }} />
                                <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', flex: 1 }}>
                                    {data.image_url}
                                </span>
                            </div>
                        )}
                        {data.text && (
                            <div dangerouslySetInnerHTML={{ __html: renderPreview(data.text) }} />
                        )}
                    </div>
                </div>
            )}

            <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0', fontFamily: FONT }}>
                Use <code style={{ background: '#f3f4f6', padding: '0 3px', borderRadius: 3 }}>{'{{variavel}}'}</code> para interpolação.
            </p>
        </>
    );
}

function InputForm({ data, update, textareaRef, saveCursor, variables }) {
    const branches = data.branches || [];
    const addBranch = () => update('branches', [
        ...branches,
        { handle: `branch-${branches.length}`, keywords: [], label: `Opção ${branches.length + 1}` },
    ]);
    const updateBranch = (i, f, v) => update('branches', branches.map((b, idx) => idx === i ? { ...b, [f]: v } : b));

    return (
        <>
            <FieldGroup label="Pergunta">
                <textarea
                    ref={textareaRef}
                    data-field="text"
                    style={{ ...field.input, height: 90, resize: 'vertical' }}
                    value={data.text || ''}
                    onChange={e => update('text', e.target.value)}
                    onKeyUp={saveCursor('text')}
                    onClick={saveCursor('text')}
                    onBlur={saveCursor('text')}
                    placeholder="Qual é o seu nome?"
                />
            </FieldGroup>
            <FieldGroup label="Salvar resposta em variável">
                {variables && variables.length > 0 ? (
                    <select
                        style={{ ...field.input, cursor: 'pointer' }}
                        value={data.save_to || ''}
                        onChange={e => update('save_to', e.target.value)}
                    >
                        <option value="">— selecione uma variável —</option>
                        {variables.map(v => (
                            <option key={v.name} value={v.name}>{v.name}</option>
                        ))}
                    </select>
                ) : (
                    <div style={{
                        padding: '8px 12px',
                        background: '#fffbeb', border: '1px solid #fde68a',
                        borderRadius: 9, fontSize: 12,
                        color: '#92400e', fontFamily: "'Inter', sans-serif",
                    }}>
                        <i className="bi bi-exclamation-triangle me-1" style={{ fontSize: 11 }} />
                        Nenhuma variável definida. Adicione variáveis nas{' '}
                        <strong>Configurações do fluxo</strong>.
                    </div>
                )}
            </FieldGroup>
            <div style={{ marginBottom: 14 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                    <label style={field.label}>Branches por keyword</label>
                    <button onClick={addBranch} style={field.smallBtn}>+ Branch</button>
                </div>
                {branches.map((b, i) => (
                    <div key={i} style={{ background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: 8, padding: 10, marginBottom: 8 }}>
                        <div style={{ display: 'flex', gap: 6, marginBottom: 6 }}>
                            <input style={{ ...field.input, flex: 1 }} value={b.label || ''} onChange={e => updateBranch(i, 'label', e.target.value)} placeholder="Rótulo" />
                            <button onClick={() => update('branches', branches.filter((_, idx) => idx !== i))} style={{ ...field.smallBtn, background: '#fee2e2', color: '#dc2626', border: 'none' }}>×</button>
                        </div>
                        <input
                            style={field.input}
                            value={(b.keywords || []).join(', ')}
                            onChange={e => updateBranch(i, 'keywords', e.target.value.split(',').map(k => k.trim()).filter(Boolean))}
                            placeholder="Keywords: sim, s, yes"
                        />
                    </div>
                ))}
                <p style={{ fontSize: 11, color: '#9ca3af', margin: 0 }}>Handle "Padrão" ativa quando nenhuma keyword bater.</p>
            </div>
        </>
    );
}

function ConditionForm({ data, update, allVars }) {
    const conditions  = data.conditions || [];
    const selectedVar = data.variable || '';

    const addCondition = () => update('conditions', [
        ...conditions,
        { handle: `branch-${conditions.length}`, operator: 'equals', value: '', label: `Saída ${conditions.length + 1}` },
    ]);
    const updateCond = (i, f, v) => update('conditions', conditions.map((c, idx) => idx === i ? { ...c, [f]: v } : c));
    const removeCond = (i) => update('conditions', conditions.filter((_, idx) => idx !== i));

    const applyPreset = (preset) => {
        if (preset === '$is_returning_contact') {
            update('conditions', [
                { handle: 'branch-0', operator: 'equals', value: 'sim', label: 'Já é cliente' },
                { handle: 'branch-1', operator: 'equals', value: 'não', label: 'Novo cliente' },
            ]);
        }
    };

    const FONT = "'Inter', system-ui, sans-serif";

    return (
        <>
            <FieldGroup label="Variável a testar">
                <select style={field.input} value={selectedVar} onChange={e => update('variable', e.target.value)}>
                    <option value="">— Selecione uma variável —</option>
                    <optgroup label="Variáveis de sessão">
                        {allVars.filter(v => !v.startsWith('$')).map(v => (
                            <option key={v} value={v}>{v}</option>
                        ))}
                    </optgroup>
                    <optgroup label="Dados do lead (sistema)">
                        {SYSTEM_VARS.map(v => <option key={v} value={v}>{v}</option>)}
                    </optgroup>
                </select>
            </FieldGroup>

            {/* Preset rápido para variáveis com valores conhecidos */}
            {selectedVar === '$is_returning_contact' && (
                <div style={{ marginBottom: 12, padding: '8px 10px', background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: 7 }}>
                    <div style={{ fontSize: 11, color: '#15803d', fontWeight: 600, marginBottom: 6, fontFamily: FONT }}>
                        <i className="bi bi-lightning-fill" style={{ marginRight: 4 }} />
                        Preset disponível para esta variável
                    </div>
                    <button
                        onClick={() => applyPreset('$is_returning_contact')}
                        style={{ ...field.smallBtn, background: '#15803d', color: '#fff', border: 'none', width: '100%', textAlign: 'center' }}
                    >
                        Aplicar: "Já é cliente" (sim) / "Novo cliente" (não)
                    </button>
                </div>
            )}

            <div style={{ marginBottom: 14 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                    <label style={field.label}>Ramificações</label>
                    <button onClick={addCondition} style={field.smallBtn}>+ Condição</button>
                </div>

                {conditions.length === 0 && (
                    <div style={{
                        textAlign: 'center', padding: '14px 12px',
                        background: '#f9fafb', borderRadius: 8,
                        border: '1px dashed #e5e7eb',
                        fontSize: 12, color: '#9ca3af', fontFamily: FONT,
                    }}>
                        Nenhuma condição ainda.<br />
                        <span style={{ fontSize: 11 }}>Clique em <strong>+ Condição</strong> para adicionar.</span>
                    </div>
                )}

                {conditions.map((c, i) => (
                    <div key={i} style={{
                        background: '#fff',
                        border: '1px solid #e5e7eb',
                        borderRadius: 9,
                        overflow: 'hidden',
                        marginBottom: 8,
                    }}>
                        {/* Header: rótulo do branch */}
                        <div style={{
                            display: 'flex', alignItems: 'center', gap: 6,
                            padding: '7px 10px',
                            background: '#f9fafb',
                            borderBottom: '1px solid #f0f2f7',
                        }}>
                            <i className="bi bi-arrow-right-circle" style={{ fontSize: 11, color: BLUE, flexShrink: 0 }} />
                            <span style={{ fontSize: 10, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase', letterSpacing: '0.05em', flexShrink: 0, fontFamily: FONT }}>
                                Rótulo do branch
                            </span>
                            <input
                                style={{ ...field.input, flex: 1, fontSize: 12, padding: '4px 8px' }}
                                value={c.label || ''}
                                onChange={e => updateCond(i, 'label', e.target.value)}
                                placeholder={`Saída ${i + 1}`}
                            />
                            <button
                                onClick={() => removeCond(i)}
                                style={{ background: 'none', border: 'none', color: '#d1d5db', cursor: 'pointer', fontSize: 15, padding: '0 2px', lineHeight: 1, flexShrink: 0 }}
                                title="Remover"
                            >×</button>
                        </div>

                        {/* Body: SE {{var}} [op] [valor] */}
                        <div style={{ padding: '8px 10px', display: 'flex', alignItems: 'center', gap: 6, flexWrap: 'wrap' }}>
                            <span style={{
                                fontSize: 9, fontWeight: 700, color: '#6b7280',
                                background: '#f3f4f6', padding: '3px 7px',
                                borderRadius: 5, whiteSpace: 'nowrap', fontFamily: FONT,
                                flexShrink: 0,
                            }}>
                                SE
                            </span>
                            <span style={{
                                fontSize: 9, color: BLUE, fontFamily: 'monospace',
                                background: '#eff6ff', border: `1px solid ${BLUE_BORDER}`,
                                padding: '2px 6px', borderRadius: 4,
                                whiteSpace: 'nowrap', flexShrink: 0,
                            }}>
                                {selectedVar ? `{{${selectedVar}}}` : '…'}
                            </span>
                            <select
                                style={{ ...field.input, flex: '1 1 100px', fontSize: 11, padding: '4px 6px' }}
                                value={c.operator || 'equals'}
                                onChange={e => updateCond(i, 'operator', e.target.value)}
                            >
                                {OPERATORS.map(op => <option key={op.value} value={op.value}>{op.label}</option>)}
                            </select>
                            <input
                                style={{ ...field.input, flex: '1 1 80px', fontSize: 12, padding: '4px 8px' }}
                                value={c.value || ''}
                                onChange={e => updateCond(i, 'value', e.target.value)}
                                placeholder="valor"
                            />
                        </div>
                    </div>
                ))}

                {conditions.length > 0 && (
                    <div style={{ padding: '8px 10px', background: '#fffbeb', border: '1px solid #fde68a', borderRadius: 7, fontSize: 11, color: '#92400e', fontFamily: FONT }}>
                        <div style={{ fontWeight: 700, marginBottom: 3 }}>
                            <i className="bi bi-arrow-return-left" style={{ marginRight: 4 }} />
                            O que é a saída <strong>"↩ Padrão"</strong>?
                        </div>
                        É a saída <strong>de fallback</strong>: dispara quando <strong>nenhuma</strong> das condições acima for satisfeita.
                        {selectedVar === '$is_returning_contact' && conditions.length >= 2 && (
                            <span> Como você tem "sim" e "não" cobertos, o Padrão nunca vai disparar neste nó.</span>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

function ActionForm({ data, update, pipelines, allVars, tags }) {
    const [selectedPipeline, setSelectedPipeline] = useState(null);
    useEffect(() => {
        if (data.pipeline_id && pipelines.length) {
            setSelectedPipeline(pipelines.find(p => p.id === data.pipeline_id) || null);
        }
    }, [data.pipeline_id, pipelines]);

    const headers = data.headers || [];
    const addHeader = () => update('headers', [...headers, { key: '', value: '' }]);
    const updateHeader = (i, f, v) => update('headers', headers.map((h, idx) => idx === i ? { ...h, [f]: v } : h));

    return (
        <>
            <FieldGroup label="Tipo de ação">
                <select style={field.input} value={data.type || ''} onChange={e => update('type', e.target.value)}>
                    <option value="">Selecione…</option>
                    {ACTION_TYPES.map(a => <option key={a.value} value={a.value}>{a.label}</option>)}
                </select>
            </FieldGroup>

            {data.type === 'change_stage' && (
                <>
                    <FieldGroup label="Pipeline">
                        <select style={field.input} value={data.pipeline_id || ''} onChange={e => {
                            const p = pipelines.find(p => p.id === parseInt(e.target.value));
                            setSelectedPipeline(p || null);
                            update('pipeline_id', p ? p.id : null);
                            update('stage_id', null);
                            update('stage_name', null);
                        }}>
                            <option value="">Selecione…</option>
                            {pipelines.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                    </FieldGroup>
                    {selectedPipeline && (
                        <FieldGroup label="Etapa">
                            <select style={field.input} value={data.stage_id || ''} onChange={e => {
                                const st = selectedPipeline.stages.find(s => s.id === parseInt(e.target.value));
                                update('stage_id', st ? st.id : null);
                                update('stage_name', st ? st.name : null);
                            }}>
                                <option value="">Selecione…</option>
                                {selectedPipeline.stages.map(st => <option key={st.id} value={st.id}>{st.name}</option>)}
                            </select>
                        </FieldGroup>
                    )}
                </>
            )}

            {(data.type === 'add_tag' || data.type === 'remove_tag') && (
                <FieldGroup label="Tag">
                    {tags && tags.length > 0 ? (
                        <select
                            style={{ ...field.input, cursor: 'pointer' }}
                            value={data.value || ''}
                            onChange={e => update('value', e.target.value)}
                        >
                            <option value="">— selecione uma tag —</option>
                            {tags.map(t => <option key={t} value={t}>{t}</option>)}
                        </select>
                    ) : (
                        <input
                            style={field.input}
                            value={data.value || ''}
                            onChange={e => update('value', e.target.value)}
                            placeholder="qualificado"
                        />
                    )}
                    {tags && tags.length === 0 && (
                        <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0' }}>
                            Nenhuma tag encontrada nos leads. Digite o nome manualmente.
                        </p>
                    )}
                </FieldGroup>
            )}

            {data.type === 'save_variable' && (
                <>
                    <FieldGroup label="Variável de destino">
                        <select style={field.input} value={data.variable || ''} onChange={e => update('variable', e.target.value)}>
                            <option value="">Selecione…</option>
                            {allVars.filter(v => !v.startsWith('$')).map(v => <option key={v} value={v}>{v}</option>)}
                        </select>
                    </FieldGroup>
                    <FieldGroup label="Valor">
                        <input style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)} placeholder={'Valor ou {{variavel}}'} />
                    </FieldGroup>
                </>
            )}

            {data.type === 'send_webhook' && (
                <>
                    <FieldGroup label="Método">
                        <select style={field.input} value={data.method || 'POST'} onChange={e => update('method', e.target.value)}>
                            {['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].map(m => <option key={m} value={m}>{m}</option>)}
                        </select>
                    </FieldGroup>
                    <FieldGroup label="URL">
                        <input style={field.input} value={data.url || ''} onChange={e => update('url', e.target.value)} placeholder="https://hook.exemplo.com/..." />
                    </FieldGroup>
                    <div style={{ marginBottom: 14 }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
                            <label style={field.label}>Headers</label>
                            <button onClick={addHeader} style={field.smallBtn}>+ Header</button>
                        </div>
                        {headers.map((h, i) => (
                            <div key={i} style={{ display: 'flex', gap: 4, marginBottom: 4 }}>
                                <input style={{ ...field.input, flex: 1 }} value={h.key} onChange={e => updateHeader(i, 'key', e.target.value)} placeholder="Key" />
                                <input style={{ ...field.input, flex: 2 }} value={h.value} onChange={e => updateHeader(i, 'value', e.target.value)} placeholder="Value" />
                                <button onClick={() => update('headers', headers.filter((_, idx) => idx !== i))} style={{ ...field.smallBtn, padding: '4px 8px' }}>×</button>
                            </div>
                        ))}
                    </div>
                    <FieldGroup label="Body (JSON)">
                        <textarea style={{ ...field.input, height: 90, resize: 'vertical', fontFamily: 'monospace', fontSize: 12 }} value={data.body || ''} onChange={e => update('body', e.target.value)} placeholder={'{"nome": "{{nome}}"}'} />
                    </FieldGroup>
                    <FieldGroup label="Salvar resposta em variável">
                        <input style={field.input} value={data.save_response_to || ''} onChange={e => update('save_response_to', e.target.value)} placeholder="webhook_result" />
                    </FieldGroup>
                </>
            )}
        </>
    );
}

function EndForm({ data, update, textareaRef, saveCursor }) {
    return (
        <FieldGroup label="Mensagem final (opcional)">
            <textarea
                ref={textareaRef}
                data-field="text"
                style={{ ...field.input, height: 100, resize: 'vertical' }}
                value={data.text || ''}
                onChange={e => update('text', e.target.value)}
                onKeyUp={saveCursor('text')}
                onClick={saveCursor('text')}
                onBlur={saveCursor('text')}
                placeholder={'Obrigado! Nossa equipe entrará em contato.'}
            />
        </FieldGroup>
    );
}

function NodePanel({ node, onUpdate, onDelete, variables, pipelines, tags }) {
    const [data, setData] = useState(node.data);
    const cfg = NODE_TYPES_CONFIG[node.type] || NODE_TYPES_CONFIG.message;
    const textareaRef  = useRef(null);
    const lastCursor   = useRef({ field: 'text', start: 0, end: 0 });

    useEffect(() => { setData(node.data); }, [node.id]);

    const update = (f, v) => {
        const nd = { ...data, [f]: v };
        setData(nd);
        onUpdate(node.id, nd);
    };

    // Track cursor position so insertVar knows where to insert
    const saveCursor = (fieldName) => (e) => {
        lastCursor.current = {
            field: fieldName,
            start: e.target.selectionStart,
            end:   e.target.selectionEnd,
        };
        textareaRef.current = e.target;
    };

    const insertVar = (varName) => {
        const insertion = `{{${varName}}}`;
        const { field: f, start, end } = lastCursor.current;
        const current = data[f] || '';
        const newVal  = current.substring(0, start) + insertion + current.substring(end);
        update(f, newVal);
        // Restore focus + cursor after React re-render
        requestAnimationFrame(() => {
            const ta = textareaRef.current;
            if (ta) {
                ta.focus();
                ta.selectionStart = ta.selectionEnd = start + insertion.length;
            }
        });
    };

    const allVars = [...variables.map(v => v.name), ...SYSTEM_VARS];

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
            {/* Panel header */}
            <div style={{
                padding: '12px 16px',
                borderBottom: '1px solid #e5e7eb',
                display: 'flex', alignItems: 'center', gap: 10,
                background: '#f9fafb', flexShrink: 0,
            }}>
                <div style={{
                    width: 32, height: 32, borderRadius: 8,
                    background: BLUE, display: 'flex', alignItems: 'center', justifyContent: 'center',
                    flexShrink: 0,
                }}>
                    <i className={`bi bi-${cfg.icon}`} style={{ fontSize: 13, color: '#fff' }} />
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#111827' }}>{cfg.label}</div>
                    <div style={{ fontSize: 11, color: '#9ca3af' }}>Editando nó</div>
                </div>
            </div>

            {/* Delete button — visible and prominent */}
            <div style={{ padding: '10px 16px', borderBottom: '1px solid #f3f4f6', flexShrink: 0 }}>
                <button
                    onClick={() => onDelete(node.id)}
                    style={{
                        width: '100%',
                        background: '#fff', border: '1px solid #fca5a5',
                        color: '#dc2626', borderRadius: 7,
                        padding: '7px 12px', cursor: 'pointer',
                        fontSize: 12, fontWeight: 600,
                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 6,
                    }}
                >
                    <i className="bi bi-trash3" />
                    Excluir este nó
                </button>
            </div>

            {/* Scroll area */}
            <div style={{ flex: 1, overflowY: 'auto', padding: '14px 16px' }}>
                <FieldGroup label="Rótulo interno (opcional)">
                    <input
                        style={field.input}
                        value={data.label || ''}
                        onChange={e => update('label', e.target.value)}
                        placeholder="Ex: Boas-vindas"
                    />
                </FieldGroup>

                <div style={{ height: 1, background: '#f0f0f0', margin: '4px 0 14px' }} />

                {node.type === 'message'   && <MessageForm data={data} update={update} textareaRef={textareaRef} saveCursor={saveCursor} />}
                {node.type === 'input'     && <InputForm   data={data} update={update} textareaRef={textareaRef} saveCursor={saveCursor} variables={variables} />}
                {node.type === 'condition' && <ConditionForm data={data} update={update} allVars={allVars} />}
                {node.type === 'action'    && <ActionForm data={data} update={update} pipelines={pipelines} allVars={allVars} tags={tags} />}
                {node.type === 'end'       && <EndForm data={data} update={update} textareaRef={textareaRef} saveCursor={saveCursor} />}

                {/* Variables — clickable chips that insert at cursor */}
                <div style={{
                    marginTop: 8, padding: '10px 12px',
                    background: '#f8faff', border: '1px solid #e0eaff',
                    borderRadius: 8, fontSize: 11, color: '#6b7280',
                }}>
                    <div style={{ fontWeight: 700, color: '#374151', marginBottom: 8 }}>
                        <i className="bi bi-code-slash" style={{ marginRight: 5, color: BLUE, fontSize: 10 }} />
                        Variáveis disponíveis
                        <span style={{ fontSize: 10, fontWeight: 400, color: '#9ca3af', marginLeft: 6 }}>
                            clique para inserir
                        </span>
                    </div>

                    {/* Session variables */}
                    {variables.length > 0 && (
                        <div style={{ marginBottom: 8 }}>
                            <div style={{ fontSize: 10, fontWeight: 600, color: '#059669', marginBottom: 4, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Sessão</div>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                                {variables.map(v => (
                                    <button
                                        key={v.name}
                                        onClick={() => insertVar(v.name)}
                                        style={{
                                            background: '#f0fdf4', border: '1px solid #bbf7d0',
                                            color: '#15803d', borderRadius: 5, padding: '2px 7px',
                                            fontSize: 11, fontFamily: "'Inter', sans-serif",
                                            cursor: 'pointer', fontWeight: 500,
                                        }}
                                    >
                                        {`{{${v.name}}}`}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* System variables */}
                    <div>
                        <div style={{ fontSize: 10, fontWeight: 600, color: '#7c3aed', marginBottom: 4, textTransform: 'uppercase', letterSpacing: '0.05em' }}>Sistema</div>
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                            {SYSTEM_VARS.map(v => (
                                <button
                                    key={v}
                                    onClick={() => insertVar(v)}
                                    style={{
                                        background: '#faf5ff', border: '1px solid #e9d5ff',
                                        color: '#6d28d9', borderRadius: 5, padding: '2px 7px',
                                        fontSize: 11, fontFamily: "'Inter', sans-serif",
                                        cursor: 'pointer', fontWeight: 500,
                                    }}
                                >
                                    {v}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ── Variables Panel ───────────────────────────────────────────────────────────

function VariablesPanel({ variables, setVariables, onClose }) {
    return (
        <div style={{ width: 230, borderRight: '1px solid #e5e7eb', background: '#fff', display: 'flex', flexDirection: 'column', flexShrink: 0 }}>
            <div style={{ padding: '12px 14px', borderBottom: '1px solid #e5e7eb', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#f9fafb' }}>
                <div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#111827' }}>Variáveis</div>
                    <div style={{ fontSize: 11, color: '#9ca3af' }}>Dados coletados no fluxo</div>
                </div>
                <button onClick={onClose} style={{ ...field.smallBtn, padding: '3px 8px', lineHeight: 1 }}>×</button>
            </div>
            <div style={{ flex: 1, overflowY: 'auto', padding: '12px 14px' }}>
                {variables.map((v, i) => (
                    <div key={i} style={{ display: 'flex', gap: 6, marginBottom: 6, alignItems: 'center' }}>
                        <input
                            style={{ ...field.input, flex: 1, fontSize: 12 }}
                            value={v.name}
                            onChange={e => setVariables(variables.map((x, idx) => idx === i ? { ...x, name: e.target.value } : x))}
                            placeholder="nome_variavel"
                        />
                        <button onClick={() => setVariables(variables.filter((_, idx) => idx !== i))} style={{ background: 'none', border: 'none', color: '#d1d5db', cursor: 'pointer', fontSize: 16, padding: '0 2px' }}>×</button>
                    </div>
                ))}
                <button onClick={() => setVariables([...variables, { name: '', default: '' }])} style={{ ...field.smallBtn, width: '100%', marginTop: 4, textAlign: 'center' }}>
                    + Nova variável
                </button>

                <div style={{ marginTop: 20 }}>
                    <div style={{ fontSize: 10, fontWeight: 700, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 8 }}>
                        Sistema (leitura)
                    </div>
                    {SYSTEM_VARS.map(v => (
                        <div key={v} style={{
                            fontSize: 11, color: '#7c3aed',
                            background: '#faf5ff', border: '1px solid #ede9fe',
                            borderRadius: 5, padding: '3px 8px', marginBottom: 4,
                            fontFamily: 'monospace',
                        }}>
                            {v}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ── Trigger Panel ─────────────────────────────────────────────────────────────

function TriggerPanel({ keywords, setKeywords, onClose }) {
    const [newKw, setNewKw] = useState('');

    const addKw = () => {
        const kw = newKw.trim().toLowerCase();
        if (kw && !keywords.includes(kw)) {
            setKeywords([...keywords, kw]);
            setNewKw('');
        }
    };

    return (
        <div style={{ width: 240, borderRight: '1px solid #e5e7eb', background: '#fff', display: 'flex', flexDirection: 'column', flexShrink: 0 }}>
            <div style={{ padding: '12px 14px', borderBottom: '1px solid #e5e7eb', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#f9fafb' }}>
                <div>
                    <div style={{ fontSize: 13, fontWeight: 700, color: '#111827', fontFamily: "'Inter', sans-serif" }}>Trigger</div>
                    <div style={{ fontSize: 11, color: '#9ca3af', fontFamily: "'Inter', sans-serif" }}>Palavras que ativam o fluxo</div>
                </div>
                <button onClick={onClose} style={{ ...field.smallBtn, padding: '3px 8px', lineHeight: 1 }}>×</button>
            </div>
            <div style={{ flex: 1, overflowY: 'auto', padding: '12px 14px' }}>
                <p style={{ fontSize: 11, color: '#6b7280', marginTop: 0, marginBottom: 10, fontFamily: "'Inter', sans-serif", lineHeight: 1.5 }}>
                    Quando um contato enviar uma dessas palavras, este fluxo será iniciado automaticamente.
                </p>
                <div style={{ display: 'flex', gap: 6, marginBottom: 12 }}>
                    <input
                        style={{ ...field.input, flex: 1, fontSize: 12 }}
                        value={newKw}
                        onChange={e => setNewKw(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && addKw()}
                        placeholder="Ex: olá, oi, início…"
                    />
                    <button
                        onClick={addKw}
                        style={{ ...field.smallBtn, background: BLUE, color: '#fff', border: 'none', padding: '4px 10px', flexShrink: 0 }}
                    >+</button>
                </div>
                {keywords.length === 0 && (
                    <div style={{ fontSize: 12, color: '#9ca3af', textAlign: 'center', padding: '14px 0', fontFamily: "'Inter', sans-serif" }}>
                        Nenhuma keyword.<br />
                        <span style={{ fontSize: 11 }}>O fluxo só iniciará manualmente.</span>
                    </div>
                )}
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                    {keywords.map((kw, i) => (
                        <div key={i} style={{
                            display: 'inline-flex', alignItems: 'center', gap: 4,
                            background: BLUE_LIGHT, border: `1px solid ${BLUE_BORDER}`,
                            color: BLUE, borderRadius: 99, padding: '3px 10px', fontSize: 12, fontWeight: 600,
                            fontFamily: "'Inter', sans-serif",
                        }}>
                            {kw}
                            <button
                                onClick={() => setKeywords(keywords.filter((_, idx) => idx !== i))}
                                style={{ background: 'none', border: 'none', color: '#93c5fd', cursor: 'pointer', fontSize: 14, padding: 0, lineHeight: 1 }}
                            >×</button>
                        </div>
                    ))}
                </div>
                <p style={{ fontSize: 10, color: '#9ca3af', marginTop: 14, fontFamily: "'Inter', sans-serif" }}>
                    <i className="bi bi-info-circle" style={{ marginRight: 4 }} />
                    Salve o fluxo após editar as keywords.
                </p>
            </div>
        </div>
    );
}

// ── Edge defaults ─────────────────────────────────────────────────────────────

const defaultEdgeOptions = {
    type: 'smoothstep',
    animated: true,
    style: { stroke: BLUE, strokeWidth: 2 },
    markerEnd: { type: MarkerType.ArrowClosed, color: BLUE, width: 14, height: 14 },
};

// ── Main Builder ──────────────────────────────────────────────────────────────

function ChatbotBuilder() {
    const cfg = window.chatbotBuilderData || {};
    const { screenToFlowPosition } = useReactFlow();

    const initialNodes = (cfg.nodes || []).map(n => ({ ...n, type: n.type || 'message' }));
    const initialEdges = (cfg.edges || []).map(e => ({
        ...e,
        animated: true,
        style: defaultEdgeOptions.style,
        markerEnd: defaultEdgeOptions.markerEnd,
    }));

    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);
    const [selectedNode, setSelectedNode] = useState(null);
    const [variables, setVariables] = useState(cfg.flow?.variables || []);
    const [triggerKeywords, setTriggerKeywords] = useState(cfg.flow?.trigger_keywords || []);
    const [pipelines, setPipelines] = useState([]);
    const tags = cfg.tags || [];
    const [isActive, setIsActive] = useState(!!cfg.flow?.is_active);
    const [saving, setSaving] = useState(false);
    const [saveMsg, setSaveMsg] = useState('');
    const [showVars, setShowVars] = useState(false);
    const [showTrigger, setShowTrigger] = useState(false);

    // Nó de início: o que não é alvo de nenhuma edge
    const startNodeId = useMemo(() => {
        const targetIds = new Set(edges.map(e => e.target));
        const startNode = nodes.find(n => !targetIds.has(n.id));
        return startNode?.id ?? null;
    }, [nodes, edges]);

    // Injeta _isStart no data dos nós para o badge "INÍCIO DO FLUXO"
    const displayNodes = useMemo(() =>
        nodes.map(n => ({ ...n, data: { ...n.data, _isStart: n.id === startNodeId } })),
    [nodes, startNodeId]);

    useEffect(() => {
        if (cfg.pipelinesUrl) {
            fetch(cfg.pipelinesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json()).then(setPipelines).catch(() => {});
        }
    }, []);

    useEffect(() => {
        if (selectedNode) {
            const updated = nodes.find(n => n.id === selectedNode.id);
            if (updated) setSelectedNode(updated);
        }
    }, [nodes]);

    const onConnect = useCallback((params) => {
        setEdges(eds => addEdge({ ...params, ...defaultEdgeOptions }, eds));
    }, [setEdges]);

    const onNodeClick  = useCallback((_, node) => setSelectedNode(node), []);
    const onPaneClick  = useCallback(() => setSelectedNode(null), []);

    const addNode = (type) => {
        const id = genId();
        setNodes(ns => [...ns, {
            id, type,
            position: screenToFlowPosition({ x: window.innerWidth / 2 - 110, y: window.innerHeight / 2 - 60 }),
            data: { label: '' },
        }]);
    };

    const updateNodeData = useCallback((nodeId, newData) => {
        setNodes(ns => ns.map(n => n.id === nodeId ? { ...n, data: newData } : n));
    }, [setNodes]);

    const deleteNode = useCallback((nodeId) => {
        setNodes(ns => ns.filter(n => n.id !== nodeId));
        setEdges(es => es.filter(e => e.source !== nodeId && e.target !== nodeId));
        setSelectedNode(null);
    }, [setNodes, setEdges]);

    const handleToggle = async () => {
        const nv = !isActive;
        setIsActive(nv);
        try {
            await fetch(cfg.toggleUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
        } catch {}
    };

    const handleSave = async () => {
        setSaving(true); setSaveMsg('');
        try {
            const payload = {
                nodes: nodes.map(n => ({ id: n.id, type: n.type, position: n.position, data: n.data })),
                edges: edges.map(e => ({ id: e.id, source: e.source, sourceHandle: e.sourceHandle || 'default', target: e.target })),
                trigger_keywords: triggerKeywords,
            };
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });
            setSaveMsg(res.ok ? 'Salvo' : 'Erro ao salvar');
        } catch {
            setSaveMsg('Erro de conexão');
        } finally {
            setSaving(false);
            setTimeout(() => setSaveMsg(''), 3000);
        }
    };

    // Toolbar: uniform buttons
    const nodeButtons = Object.entries(NODE_TYPES_CONFIG).map(([type, c]) => (
        <button
            key={type}
            onClick={() => addNode(type)}
            title={`Adicionar nó ${c.label}`}
            style={{
                display: 'flex', alignItems: 'center', gap: 5,
                background: '#fff', border: '1px solid #e5e7eb',
                color: '#374151', borderRadius: 7,
                padding: '5px 11px', cursor: 'pointer',
                fontSize: 12, fontWeight: 600,
            }}
        >
            <i className={`bi bi-${c.icon}`} style={{ fontSize: 11, color: BLUE }} />
            {c.label}
        </button>
    ));

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%', background: '#f8fafc' }}>

            {/* ── Toolbar — barra fixa full-width, nunca espreme ── */}
            <div style={{
                flexShrink: 0,
                display: 'flex', gap: 6, alignItems: 'center', flexWrap: 'nowrap',
                padding: '8px 14px',
                background: '#fff',
                borderBottom: '1px solid #e5e7eb',
                boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
                overflowX: 'auto',
            }}>
                {nodeButtons}

                <div style={{ width: 1, height: 24, background: '#e5e7eb', margin: '0 2px', flexShrink: 0 }} />

                {/* Trigger toggle */}
                <button
                    onClick={() => { setShowTrigger(t => !t); setShowVars(false); }}
                    style={{
                        ...field.smallBtn,
                        background: showTrigger ? '#fef3c7' : '#f9fafb',
                        color: showTrigger ? '#92400e' : '#374151',
                        border: `1px solid ${showTrigger ? '#fde68a' : '#e5e7eb'}`,
                        display: 'flex', alignItems: 'center', gap: 5, flexShrink: 0,
                    }}
                >
                    <i className="bi bi-lightning-charge" style={{ fontSize: 11 }} />
                    Trigger
                    {triggerKeywords.length > 0 && (
                        <span style={{ background: BLUE, color: '#fff', borderRadius: 99, fontSize: 10, fontWeight: 700, padding: '1px 6px' }}>
                            {triggerKeywords.length}
                        </span>
                    )}
                </button>

                {/* Variables toggle */}
                <button
                    onClick={() => { setShowVars(v => !v); setShowTrigger(false); }}
                    style={{
                        ...field.smallBtn,
                        background: showVars ? BLUE_LIGHT : '#f9fafb',
                        color: showVars ? BLUE : '#374151',
                        border: `1px solid ${showVars ? BLUE_BORDER : '#e5e7eb'}`,
                        display: 'flex', alignItems: 'center', gap: 5, flexShrink: 0,
                    }}
                >
                    <i className="bi bi-code-slash" style={{ fontSize: 11 }} />
                    Variáveis
                </button>

                {/* Active toggle */}
                <div
                    style={{ display: 'flex', alignItems: 'center', gap: 7, cursor: 'pointer', userSelect: 'none', flexShrink: 0 }}
                    onClick={handleToggle}
                >
                    <div style={{ width: 38, height: 22, background: isActive ? '#10b981' : '#d1d5db', borderRadius: 11, position: 'relative', transition: 'background .2s', flexShrink: 0 }}>
                        <div style={{ width: 17, height: 17, background: '#fff', borderRadius: '50%', position: 'absolute', top: 2.5, left: isActive ? 18 : 2.5, transition: 'left .2s', boxShadow: '0 1px 3px rgba(0,0,0,0.15)' }} />
                    </div>
                    <span style={{ fontSize: 12, fontWeight: 600, color: isActive ? '#059669' : '#6b7280' }}>
                        {isActive ? 'Ativo' : 'Inativo'}
                    </span>
                </div>

                <div style={{ width: 1, height: 24, background: '#e5e7eb', margin: '0 2px', flexShrink: 0 }} />

                {/* Save */}
                <button
                    onClick={handleSave}
                    disabled={saving}
                    style={{
                        background: saving ? '#93c5fd' : BLUE,
                        color: '#fff', border: 'none',
                        borderRadius: 8, padding: '6px 16px',
                        cursor: saving ? 'not-allowed' : 'pointer',
                        fontSize: 12, fontWeight: 700, flexShrink: 0,
                        display: 'flex', alignItems: 'center', gap: 6,
                    }}
                >
                    <i className={`bi bi-${saving ? 'hourglass-split' : 'floppy'}`} style={{ fontSize: 11 }} />
                    {saving ? 'Salvando…' : 'Salvar'}
                </button>

                {saveMsg && (
                    <span style={{ fontSize: 12, fontWeight: 600, color: saveMsg === 'Salvo' ? '#059669' : '#dc2626', flexShrink: 0 }}>
                        <i className={`bi bi-${saveMsg === 'Salvo' ? 'check-lg' : 'x-lg'}`} style={{ marginRight: 4 }} />
                        {saveMsg}
                    </span>
                )}
            </div>

            {/* ── Canvas area + side panels ── */}
            <div style={{ flex: 1, display: 'flex', overflow: 'hidden' }}>

                {/* Left: trigger panel */}
                {showTrigger && (
                    <TriggerPanel keywords={triggerKeywords} setKeywords={setTriggerKeywords} onClose={() => setShowTrigger(false)} />
                )}

                {/* Left: variables panel */}
                {showVars && (
                    <VariablesPanel variables={variables} setVariables={setVariables} onClose={() => setShowVars(false)} />
                )}

                {/* Center: canvas */}
                <div style={{ flex: 1, position: 'relative', overflow: 'hidden' }}>
                    <ReactFlow
                        nodes={displayNodes}
                        edges={edges}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        nodeTypes={nodeTypes}
                        defaultEdgeOptions={defaultEdgeOptions}
                        fitView
                        fitViewOptions={{ padding: 0.3 }}
                        deleteKeyCode="Delete"
                        style={{ background: '#f8fafc' }}
                    >
                        <Controls style={{ bottom: 16, left: 16 }} />
                        <MiniMap zoomable pannable style={{ bottom: 16, right: 16 }} />
                        <Background variant={BackgroundVariant.Dots} color="#cbd5e1" gap={20} size={1.5} />
                    </ReactFlow>
                </div>

                {/* Right: node edit panel */}
                {selectedNode && (
                    <div style={{
                        width: 300, flexShrink: 0,
                        borderLeft: '1px solid #e5e7eb',
                        background: '#fff',
                        display: 'flex', flexDirection: 'column',
                        boxShadow: '-2px 0 12px rgba(0,0,0,0.04)',
                    }}>
                        <NodePanel
                            key={selectedNode.id}
                            node={selectedNode}
                            onUpdate={updateNodeData}
                            onDelete={deleteNode}
                            variables={variables}
                            pipelines={pipelines}
                            tags={tags}
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

function App() {
    return (
        <ReactFlowProvider>
            <ChatbotBuilder />
        </ReactFlowProvider>
    );
}

const el = document.getElementById('chatbot-builder-root');
if (el) createRoot(el).render(<App />);
