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
    getSmoothStepPath,
    BaseEdge,
    EdgeLabelRenderer,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

// ── Constantes ────────────────────────────────────────────────────────────────

const BLUE        = '#2563eb';
const BLUE_LIGHT  = '#eff6ff';
const BLUE_BORDER = '#bfdbfe';

// Bootstrap Icons (bi bi-*) — FontAwesome não está instalado no projeto
// Channel-aware node config: message/input vary by channel, others are universal
const CHANNEL_NODE_CFG = {
    message: {
        whatsapp:  { label: 'WhatsApp Oficial', sublabel: 'Enviar mensagem',   icon: 'whatsapp',   color: '#25d366' },
        instagram: { label: 'Instagram',        sublabel: 'Enviar DM',         icon: 'instagram',  color: '#e1306c' },
        website:   { label: 'Chat',             sublabel: 'Enviar mensagem',   icon: 'chat-dots',  color: '#2563eb' },
    },
    input: {
        whatsapp:  { label: 'WhatsApp Oficial', sublabel: 'Aguardar resposta', icon: 'whatsapp',   color: '#25d366' },
        instagram: { label: 'Instagram',        sublabel: 'Aguardar resposta', icon: 'instagram',  color: '#e1306c' },
        website:   { label: 'Chat',             sublabel: 'Aguardar resposta', icon: 'keyboard',   color: '#7c3aed' },
    },
};

const NODE_TYPES_CONFIG = {
    start:     { label: 'Iniciar quando...', sublabel: '',              icon: 'play-fill',        color: '#10b981' },
    message:   { label: 'Mensagem',          sublabel: '',              icon: 'chat-dots',        color: '#25d366' },
    input:     { label: 'Pergunta',          sublabel: '',              icon: 'keyboard',          color: '#7c3aed' },
    condition: { label: 'Condição',          sublabel: '',              icon: 'diagram-2',         color: '#ea580c' },
    action:    { label: 'Ações',             sublabel: '',              icon: 'lightning-charge',  color: '#f59e0b' },
    delay:     { label: 'Atraso',            sublabel: '',              icon: 'clock-history',     color: '#ef4444' },
    end:       { label: 'Fim do fluxo',      sublabel: '',              icon: 'stop-circle',       color: '#6b7280' },
};

function getNodeCfg(type) {
    const channel = (window.chatbotBuilderData?.flow?.channel) || 'whatsapp';
    if (CHANNEL_NODE_CFG[type]?.[channel]) return CHANNEL_NODE_CFG[type][channel];
    return NODE_TYPES_CONFIG[type] || NODE_TYPES_CONFIG.message;
}

// Tipos disponíveis na sidebar (start não aparece — criado automaticamente)
const SIDEBAR_NODE_TYPES = [
    { type: 'message',   label: 'Mensagem', icon: 'chat-dots',        color: '#25d366' },
    { type: 'input',     label: 'Pergunta', icon: 'keyboard',          color: '#7c3aed' },
    { type: 'condition', label: 'Condição', icon: 'diagram-2',         color: '#ea580c' },
    { type: 'action',    label: 'Ação',     icon: 'lightning-charge',  color: '#f59e0b' },
    { type: 'delay',     label: 'Aguardar', icon: 'hourglass-split',   color: '#ef4444' },
    { type: 'end',       label: 'Fim',      icon: 'stop-circle',       color: '#6b7280' },
];

const ACTION_TYPES = [
    { value: 'create_lead',        label: 'Criar Lead'                      },
    { value: 'change_stage',       label: 'Trocar etapa do funil'          },
    { value: 'add_tag',            label: 'Adicionar tag'                   },
    { value: 'remove_tag',         label: 'Remover tag'                     },
    { value: 'assign_human',       label: 'Transferir para humano'          },
    { value: 'close_conversation', label: 'Fechar conversa'                 },
    { value: 'save_variable',      label: 'Salvar variável'                 },
    { value: 'send_webhook',       label: 'Enviar Webhook (HTTP)'           },
    { value: 'set_custom_field',   label: 'Preencher campo personalizado'   },
    { value: 'send_whatsapp',      label: 'Enviar WhatsApp'                },
    { value: 'create_task',        label: 'Criar tarefa'                    },
    { value: 'redirect',           label: 'Redirecionar (Website)'          },
];

const SYSTEM_VARS_META = [
    { key: '$lead_exists',          label: 'Lead Existe?'        },
    { key: '$lead_stage_name',      label: 'Etapa do Lead'       },
    { key: '$lead_stage_id',        label: 'ID da Etapa'         },
    { key: '$lead_source',          label: 'Origem do Lead'      },
    { key: '$lead_tags',            label: 'Tags do Lead'        },
    { key: '$conversations_count',  label: 'Total de Conversas'  },
    { key: '$is_returning_contact', label: 'Contato Recorrente?' },
    { key: '$messages_count',       label: 'Total de Mensagens'  },
    { key: '$contact_phone',        label: 'Telefone'            },
    { key: '$contact_name',         label: 'Nome do Contato'     },
];
const SYSTEM_VARS = SYSTEM_VARS_META.map(v => v.key);

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
    const cfg = getNodeCfg(type);
    const isAction = type === 'action';
    const FONT = "'Inter', system-ui, sans-serif";
    const HANDLE_COLOR = '#3b82f6';

    return (
        <div style={{
            background: '#fff',
            border: `1.5px solid ${selected ? HANDLE_COLOR : '#e5e7eb'}`,
            borderRadius: 14,
            width: 300,
            position: 'relative',
            boxShadow: selected
                ? `0 0 0 3px ${HANDLE_COLOR}18, 0 4px 16px rgba(0,0,0,0.08)`
                : '0 2px 12px rgba(0,0,0,0.06)',
            fontFamily: FONT,
            overflow: 'visible',
        }}>

            {/* LEFT: target handle */}
            <Handle
                type="target"
                position={Position.Left}
                style={{
                    background: '#fff',
                    width: 11, height: 11,
                    border: `2px solid ${HANDLE_COLOR}`,
                    left: -6,
                    top: '50%',
                    transform: 'translateY(-50%)',
                }}
            />

            {/* Header — white background, colored icon (like Kommo reference) */}
            <div style={{
                padding: '10px 14px',
                display: 'flex', alignItems: 'center', gap: 8,
                borderBottom: '1px solid #f0f2f7',
                borderRadius: '14px 14px 0 0',
            }}>
                <i className={`bi bi-${cfg.icon}`} style={{ fontSize: 16, color: cfg.color, flexShrink: 0 }} />
                <div style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ fontSize: 11, color: '#9ca3af', fontWeight: 500, lineHeight: 1.2, fontFamily: FONT }}>
                        {cfg.label}
                    </div>
                    {cfg.sublabel && (
                        <div style={{ fontSize: 13, color: '#1a1d23', fontWeight: 700, lineHeight: 1.3, fontFamily: FONT }}>
                            {cfg.sublabel}
                        </div>
                    )}
                    {!cfg.sublabel && !isAction && (
                        <div style={{ fontSize: 13, color: '#1a1d23', fontWeight: 700, lineHeight: 1.3, fontFamily: FONT }}>
                            {cfg.label}
                        </div>
                    )}
                </div>
                {/* Delete icon */}
                <div style={{
                    width: 22, height: 22,
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    flexShrink: 0, cursor: 'pointer', borderRadius: 6,
                    color: '#d1d5db', transition: 'color .15s',
                }}>
                    <i className="bi bi-trash3" style={{ fontSize: 11 }} />
                </div>
            </div>

            {/* Body */}
            <div style={{
                padding: '10px 14px',
                fontSize: 12.5,
                color: '#374151',
                lineHeight: 1.55,
                minHeight: 28,
                fontFamily: FONT,
            }}>
                {children}
            </div>

            {/* Branch rows — pills style (like Kommo buttons) */}
            {rightHandles.length > 0 && (
                <div style={{ padding: '0 14px 8px' }}>
                    {rightHandles.map((h, i) => (
                        <div
                            key={h.id}
                            style={{
                                position: 'relative',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                padding: '6px 28px 6px 12px',
                                marginBottom: i < rightHandles.length - 1 ? 4 : 0,
                                border: '1px solid #e5e7eb',
                                borderRadius: 8,
                                background: '#fafafa',
                                overflow: 'visible',
                            }}
                        >
                            <span style={{
                                fontSize: 12, fontWeight: 500,
                                color: '#374151',
                                maxWidth: 200,
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
                                    background: HANDLE_COLOR,
                                    width: 11, height: 11,
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

            {/* Footer: "Próximo passo" + default handle */}
            {hasDefaultHandle && type !== 'end' && (
                <div style={{
                    position: 'relative',
                    padding: '8px 14px',
                    borderTop: '1px solid #f0f2f7',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'flex-end',
                    overflow: 'visible',
                }}>
                    <span style={{ fontSize: 11, color: '#9ca3af', fontWeight: 500, fontFamily: FONT, marginRight: 8 }}>
                        Próximo passo
                    </span>
                    <Handle
                        type="source"
                        position={Position.Right}
                        id="default"
                        style={{
                            background: HANDLE_COLOR,
                            width: 11, height: 11,
                            border: '2px solid #fff',
                            position: 'absolute',
                            right: -7,
                            top: '50%',
                            transform: 'translateY(-50%)',
                        }}
                    />
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
    const hasBranches = branches.length > 0;
    const rightHandles = hasBranches
        ? branches.map((b, i) => ({ id: b.handle || `branch-${i}`, label: b.label || `Opção ${i + 1}` }))
        : [];
    return (
        <BaseNode type="input" data={data} selected={selected} hasDefaultHandle={true} rightHandles={rightHandles}>
            <Preview text={data.text} />
            {data.save_to && <Tag><i className="bi bi-floppy" style={{ marginRight: 4, fontSize: 9 }} />{data.save_to}</Tag>}
        </BaseNode>
    );
}

function ConditionNode({ id, data, selected }) {
    const conditions = data.conditions || [];
    const rightHandles = conditions.map((c, i) => ({ id: c.handle || `branch-${i}`, label: c.label || `Saída ${i + 1}` }));
    return (
        <BaseNode type="condition" data={data} selected={selected} hasDefaultHandle={true} rightHandles={rightHandles}>
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
            {data.type === 'send_whatsapp' && data.message && (
                <div style={{ fontSize: 10, color: '#9ca3af', marginTop: 4, wordBreak: 'break-all' }}>
                    <i className="bi bi-whatsapp" style={{ marginRight: 4, color: '#25d366' }} />
                    {data.message.substring(0, 40)}{data.message.length > 40 ? '…' : ''}
                </div>
            )}
        </BaseNode>
    );
}

function DelayNode({ id, data, selected }) {
    const seconds = data.seconds ?? 3;
    const label   = seconds === 1 ? '1 segundo' : `${seconds} segundos`;
    return (
        <BaseNode type="delay" data={data} selected={selected} hasDefaultHandle={true}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 7 }}>
                <i className="bi bi-hourglass-split" style={{ fontSize: 16, color: '#f59e0b' }} />
                <span style={{ fontWeight: 700, color: '#92400e', fontSize: 13 }}>{label}</span>
            </div>
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

function StartNode({ id, data, selected }) {
    const FONT   = "'Inter', system-ui, sans-serif";
    const flow   = window.chatbotBuilderData?.flow || {};
    const channel = flow.channel || 'whatsapp';
    const triggerType = flow.trigger_type || 'keyword';

    const channelIcon  = { whatsapp: 'whatsapp', instagram: 'instagram', website: 'globe' }[channel] || 'chat-dots';
    const channelColor = { whatsapp: '#25d366', instagram: '#e1306c', website: '#2563eb' }[channel] || '#2563eb';
    const channelLabel = { whatsapp: 'WhatsApp', instagram: 'Instagram', website: 'Website' }[channel] || channel;

    const triggerLabel = triggerType === 'instagram_comment'
        ? 'Comentou em publicação'
        : 'Palavras-chave';

    const keywords = flow.trigger_keywords || [];

    return (
        <div style={{
            background: '#fff',
            border: `1.5px solid ${selected ? '#3b82f6' : '#e5e7eb'}`,
            borderRadius: 14,
            width: 260,
            position: 'relative',
            boxShadow: selected
                ? '0 0 0 3px rgba(59,130,246,0.1), 0 4px 16px rgba(0,0,0,0.08)'
                : '0 2px 12px rgba(0,0,0,0.06)',
            fontFamily: FONT,
        }}>
            {/* Header */}
            <div style={{
                padding: '10px 14px',
                display: 'flex', alignItems: 'center', gap: 8,
                borderBottom: '1px solid #f0f2f7',
                borderRadius: '14px 14px 0 0',
            }}>
                <div style={{
                    width: 28, height: 28, borderRadius: 8,
                    background: channelColor + '14',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
                }}>
                    <i className={`bi bi-${channelIcon}`} style={{ fontSize: 14, color: channelColor }} />
                </div>
                <div>
                    <div style={{ fontSize: 11, color: '#9ca3af', fontWeight: 500, fontFamily: FONT }}>Iniciar quando...</div>
                    <div style={{ fontSize: 12, color: '#1a1d23', fontWeight: 700, fontFamily: FONT }}>{triggerLabel}</div>
                </div>
            </div>
            {/* Body: show trigger info */}
            <div style={{ padding: '10px 14px', fontSize: 12, color: '#6b7280', lineHeight: 1.5, fontFamily: FONT }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginBottom: 4 }}>
                    <i className={`bi bi-${channelIcon}`} style={{ fontSize: 11, color: channelColor }} />
                    <span style={{ fontWeight: 600, color: '#374151' }}>{channelLabel}</span>
                </div>
                {keywords.length > 0 && (
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4, marginTop: 4 }}>
                        {keywords.slice(0, 5).map((kw, i) => (
                            <span key={i} style={{
                                padding: '2px 8px', background: '#f3f4f6', borderRadius: 99,
                                fontSize: 10, fontWeight: 600, color: '#374151',
                            }}>{kw}</span>
                        ))}
                        {keywords.length > 5 && <span style={{ fontSize: 10, color: '#9ca3af' }}>+{keywords.length - 5}</span>}
                    </div>
                )}
                {keywords.length === 0 && (
                    <span style={{ fontSize: 11, color: '#d1d5db', fontStyle: 'italic' }}>Qualquer mensagem</span>
                )}
            </div>
            {/* Footer with "Próximo passo" */}
            <div style={{
                position: 'relative', padding: '8px 14px',
                borderTop: '1px solid #f0f2f7',
                display: 'flex', alignItems: 'center', justifyContent: 'flex-end',
                overflow: 'visible',
            }}>
                <span style={{ fontSize: 11, color: '#9ca3af', fontWeight: 500, fontFamily: FONT, marginRight: 8 }}>Próximo passo</span>
                <Handle
                    type="source"
                    position={Position.Right}
                    id="default"
                    style={{
                        background: '#3b82f6',
                        width: 11, height: 11,
                        border: '2px solid #fff',
                        position: 'absolute',
                        right: -7,
                        top: '50%',
                        transform: 'translateY(-50%)',
                    }}
                />
            </div>
        </div>
    );
}

const nodeTypes = { start: StartNode, message: MessageNode, input: InputNode, condition: ConditionNode, action: ActionNode, delay: DelayNode, end: EndNode };

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
            <FieldGroup label="Tipo do campo">
                <select
                    style={{ ...field.input, cursor: 'pointer' }}
                    value={data.field_type || 'text'}
                    onChange={e => {
                        update('field_type', e.target.value);
                        const presets = { phone: 'contact_phone', email: 'contact_email', name: 'contact_name' };
                        if (presets[e.target.value]) update('save_to', presets[e.target.value]);
                    }}
                >
                    <option value="text">Texto livre</option>
                    <option value="name">Nome</option>
                    <option value="email">E-mail</option>
                    <option value="phone">Telefone (com máscara BR)</option>
                </select>
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
            <FieldGroup label="Tipo de resposta">
                <select
                    style={{ ...field.input, cursor: 'pointer' }}
                    value={data.input_type || 'text'}
                    onChange={e => {
                        const val = e.target.value;
                        update('input_type', val);
                        if (val === 'text') {
                            update('branches', []);
                            update('show_buttons', false);
                        } else {
                            update('show_buttons', true);
                            if (!branches.length) {
                                update('branches', [{ handle: 'branch-0', keywords: [], label: 'Opção 1' }]);
                            }
                        }
                    }}
                >
                    <option value="text">Texto livre</option>
                    <option value="buttons">Botões de resposta</option>
                    {(window.chatbotBuilderData?.flow?.channel) === 'whatsapp' && (
                        <option value="list">Menu interativo (lista)</option>
                    )}
                </select>
            </FieldGroup>
            {(data.input_type === 'buttons' || data.input_type === 'list' || !!data.show_buttons) && (
            <div style={{ marginBottom: 14 }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                    <label style={field.label}>Opções</label>
                    <button onClick={addBranch} style={field.smallBtn}>+ Opção</button>
                </div>
                {branches.map((b, i) => (
                    <div key={i} style={{ background: '#f9fafb', border: '1px solid #e5e7eb', borderRadius: 8, padding: 10, marginBottom: 8 }}>
                        <div style={{ display: 'flex', gap: 6, marginBottom: 6 }}>
                            <input style={{ ...field.input, flex: 1 }} value={b.label || ''} onChange={e => updateBranch(i, 'label', e.target.value)} placeholder="Texto do botão" />
                            <button onClick={() => update('branches', branches.filter((_, idx) => idx !== i))} style={{ ...field.smallBtn, background: '#fee2e2', color: '#dc2626', border: 'none' }}>×</button>
                        </div>
                        <input
                            style={field.input}
                            value={(b.keywords || []).join(', ')}
                            onChange={e => updateBranch(i, 'keywords', e.target.value.split(',').map(k => k.trim()).filter(Boolean))}
                            placeholder="Keywords alternativas (vírgula): sim, s, yes"
                        />
                    </div>
                ))}
            </div>
            )}
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
                        {SYSTEM_VARS_META.map(v => <option key={v.key} value={v.key}>{v.label} ({v.key})</option>)}
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

function ActionForm({ data, update, pipelines, allVars, tags, users, customFieldDefs }) {
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

            {data.type === 'create_lead' && (
                <>
                    <FieldGroup label="Pipeline + Etapa inicial">
                        <select style={field.input} value={data.pipeline_id || ''} onChange={e => {
                            const p = pipelines.find(p => p.id === parseInt(e.target.value));
                            setSelectedPipeline(p || null);
                            update('pipeline_id', p ? p.id : null);
                            update('stage_id', null);
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
                            }}>
                                <option value="">Selecione…</option>
                                {selectedPipeline.stages.map(st => <option key={st.id} value={st.id}>{st.name}</option>)}
                            </select>
                        </FieldGroup>
                    )}
                    <FieldGroup label="Variável → Nome do lead">
                        <select style={field.input} value={data.name_var || ''} onChange={e => update('name_var', e.target.value)}>
                            <option value="">Não mapear</option>
                            {allVars.filter(v => !v.startsWith('$')).map(v => <option key={v} value={v}>{v}</option>)}
                        </select>
                    </FieldGroup>
                    <FieldGroup label="Variável → E-mail do lead">
                        <select style={field.input} value={data.email_var || ''} onChange={e => update('email_var', e.target.value)}>
                            <option value="">Não mapear</option>
                            {allVars.filter(v => !v.startsWith('$')).map(v => <option key={v} value={v}>{v}</option>)}
                        </select>
                    </FieldGroup>
                    <FieldGroup label="Variável → Telefone do lead">
                        <select style={field.input} value={data.phone_var || ''} onChange={e => update('phone_var', e.target.value)}>
                            <option value="">Não mapear</option>
                            {allVars.filter(v => !v.startsWith('$')).map(v => <option key={v} value={v}>{v}</option>)}
                        </select>
                    </FieldGroup>
                </>
            )}

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

            {data.type === 'assign_human' && users.length > 0 && (
                <FieldGroup label="Atribuir para">
                    <select
                        style={field.input}
                        value={data.user_id || ''}
                        onChange={e => update('user_id', e.target.value ? parseInt(e.target.value) : null)}
                    >
                        <option value="">— Qualquer humano disponível —</option>
                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
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

            {data.type === 'set_custom_field' && (
                <>
                    <FieldGroup label="Campo personalizado">
                        <select
                            style={field.input}
                            value={data.field_name || ''}
                            onChange={e => {
                                const def = (customFieldDefs || []).find(d => d.name === e.target.value);
                                update('field_name', e.target.value);
                                update('field_label', def ? def.label : '');
                                update('field_type', def ? def.field_type : '');
                                update('value', '');
                            }}
                        >
                            <option value="">Selecione um campo…</option>
                            {(customFieldDefs || []).map(d => (
                                <option key={d.name} value={d.name}>{d.label}</option>
                            ))}
                        </select>
                        {(!customFieldDefs || customFieldDefs.length === 0) && (
                            <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0' }}>
                                Nenhum campo personalizado cadastrado.
                            </p>
                        )}
                    </FieldGroup>
                    {data.field_name && (() => {
                        const def = (customFieldDefs || []).find(d => d.name === data.field_name);
                        const ft  = def?.field_type || 'text';
                        const opts = def?.options || [];
                        if ((ft === 'select' || ft === 'multiselect') && opts.length > 0) {
                            return (
                                <FieldGroup label="Valor">
                                    <select style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)}>
                                        <option value="">Selecione…</option>
                                        {opts.map(o => <option key={o} value={o}>{o}</option>)}
                                    </select>
                                </FieldGroup>
                            );
                        }
                        if (ft === 'boolean' || ft === 'checkbox') {
                            return (
                                <FieldGroup label="Valor">
                                    <select style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)}>
                                        <option value="">Selecione…</option>
                                        <option value="true">Sim</option>
                                        <option value="false">Não</option>
                                    </select>
                                </FieldGroup>
                            );
                        }
                        if (ft === 'number' || ft === 'currency' || ft === 'percent') {
                            return (
                                <FieldGroup label="Valor">
                                    <input type="number" style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)} placeholder="0" />
                                </FieldGroup>
                            );
                        }
                        if (ft === 'date') {
                            return (
                                <FieldGroup label="Valor">
                                    <input type="date" style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)} />
                                </FieldGroup>
                            );
                        }
                        return (
                            <FieldGroup label="Valor">
                                <input style={field.input} value={data.value || ''} onChange={e => update('value', e.target.value)} placeholder="Valor ou {{variavel}}" />
                            </FieldGroup>
                        );
                    })()}
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
                    {/* Body builder */}
                    {(() => {
                        const isGet      = (data.method || 'POST') === 'GET';
                        const rawMode    = data.body_raw_mode || false;
                        const bodyFields = data.body_fields || [];

                        const fieldsToJson = (fields) => {
                            const obj = {};
                            fields.forEach(bf => { if (bf.key.trim()) obj[bf.key.trim()] = bf.value; });
                            return JSON.stringify(obj, null, 2);
                        };

                        const addBodyField = () => {
                            const next = [...bodyFields, { key: '', value: '' }];
                            update({ body_fields: next, body: fieldsToJson(next) });
                        };

                        const updateBodyField = (i, k, v) => {
                            const next = bodyFields.map((bf, idx) => idx === i ? { ...bf, [k]: v } : bf);
                            update({ body_fields: next, body: fieldsToJson(next) });
                        };

                        const removeBodyField = (i) => {
                            const next = bodyFields.filter((_, idx) => idx !== i);
                            update({ body_fields: next, body: fieldsToJson(next) });
                        };

                        const quickVars = [
                            { key: 'nome',     value: '{{$contact_name}}'  },
                            { key: 'telefone', value: '{{$contact_phone}}' },
                        ];

                        const toggleRaw = () => {
                            if (!rawMode) {
                                update('body_raw_mode', true);
                            } else {
                                try {
                                    const parsed = JSON.parse(data.body || '{}');
                                    const fields = Object.entries(parsed).map(([k, v]) => ({ key: k, value: String(v) }));
                                    update({ body_raw_mode: false, body_fields: fields });
                                } catch {
                                    update('body_raw_mode', false);
                                }
                            }
                        };

                        return (
                            <div style={{ marginBottom: 14 }}>
                                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
                                    <label style={field.label}>Body {!isGet ? '(JSON)' : ''}</label>
                                    {!isGet && (
                                        <button
                                            onClick={toggleRaw}
                                            style={{ ...field.smallBtn, background: rawMode ? '#dbeafe' : '#f3f4f6', color: rawMode ? '#1d4ed8' : '#374151' }}
                                        >
                                            {rawMode ? '⚡ Campos' : '{ } JSON Raw'}
                                        </button>
                                    )}
                                </div>
                                {isGet ? (
                                    <div style={{ fontSize: 11, color: '#9ca3af', padding: '4px 0' }}>
                                        GET não possui body. Passe parâmetros na URL: <code style={{ background: '#f1f5f9', padding: '1px 4px', borderRadius: 3 }}>?chave={'{{variavel}}'}</code>
                                    </div>
                                ) : rawMode ? (
                                    <textarea
                                        style={{ ...field.input, height: 110, resize: 'vertical', fontFamily: 'monospace', fontSize: 12 }}
                                        value={data.body || ''}
                                        onChange={e => update('body', e.target.value)}
                                        placeholder={'{\n  "nome": "{{nome}}",\n  "telefone": "{{$contact_phone}}"\n}'}
                                    />
                                ) : (
                                    <>
                                        {bodyFields.map((bf, i) => (
                                            <div key={i} style={{ display: 'flex', gap: 4, marginBottom: 4, alignItems: 'center' }}>
                                                <input
                                                    style={{ ...field.input, flex: '1 1 80px', fontFamily: 'monospace', fontSize: 12 }}
                                                    value={bf.key}
                                                    onChange={e => updateBodyField(i, 'key', e.target.value)}
                                                    placeholder="campo"
                                                />
                                                <span style={{ color: '#9ca3af', fontSize: 13, flexShrink: 0 }}>:</span>
                                                <input
                                                    style={{ ...field.input, flex: '2 1 100px', fontSize: 12 }}
                                                    value={bf.value}
                                                    onChange={e => updateBodyField(i, 'value', e.target.value)}
                                                    placeholder="valor ou {{variavel}}"
                                                />
                                                <button onClick={() => removeBodyField(i)} style={{ ...field.smallBtn, padding: '4px 8px', flexShrink: 0 }}>×</button>
                                            </div>
                                        ))}
                                        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginTop: 4 }}>
                                            <button onClick={addBodyField} style={field.smallBtn}>+ Campo</button>
                                            {quickVars.map(qv => (
                                                <button
                                                    key={qv.key}
                                                    onClick={() => {
                                                        const next = [...bodyFields, { key: qv.key, value: qv.value }];
                                                        update({ body_fields: next, body: fieldsToJson(next) });
                                                    }}
                                                    style={{ ...field.smallBtn, fontSize: 10, color: '#6366f1' }}
                                                >
                                                    + {qv.key}
                                                </button>
                                            ))}
                                        </div>
                                        {bodyFields.length > 0 && (
                                            <div style={{ marginTop: 6, padding: '6px 8px', background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 6, fontFamily: 'monospace', fontSize: 11, color: '#475569', whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
                                                {data.body || '{}'}
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        );
                    })()}
                    <FieldGroup label="Salvar resposta em variável">
                        <input style={field.input} value={data.save_response_to || ''} onChange={e => update('save_response_to', e.target.value)} placeholder="webhook_result" />
                    </FieldGroup>
                </>
            )}

            {data.type === 'send_whatsapp' && (
                <>
                    <FieldGroup label="Destino">
                        <select
                            style={field.input}
                            value={data.phone_mode || 'variable'}
                            onChange={e => update('phone_mode', e.target.value)}
                        >
                            <option value="variable">Variável do fluxo</option>
                            <option value="custom">Número fixo</option>
                        </select>
                    </FieldGroup>
                    {(data.phone_mode || 'variable') === 'variable' ? (
                        <FieldGroup label="Variável com telefone">
                            <select style={field.input} value={data.phone_var || '$contact_phone'} onChange={e => update('phone_var', e.target.value)}>
                                {allVars.map(v => <option key={v} value={v}>{v}</option>)}
                            </select>
                        </FieldGroup>
                    ) : (
                        <FieldGroup label="Número fixo (com DDD)">
                            <input style={field.input} value={data.custom_phone || ''} onChange={e => update('custom_phone', e.target.value)} placeholder="5511999999999" />
                        </FieldGroup>
                    )}
                    <FieldGroup label="Mensagem">
                        <textarea
                            style={{ ...field.input, height: 80, resize: 'vertical' }}
                            value={data.message || ''}
                            onChange={e => update('message', e.target.value)}
                            placeholder={'Olá {{$contact_name}}, obrigado pelo contato!'}
                        />
                        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginTop: 4 }}>
                            {[{ l: 'Nome', v: '{{$contact_name}}' }, { l: 'Telefone', v: '{{$contact_phone}}' }].map(q => (
                                <button
                                    key={q.l}
                                    type="button"
                                    onClick={() => update('message', (data.message || '') + q.v)}
                                    style={{ ...field.smallBtn, fontSize: 10, color: '#25d366' }}
                                >
                                    + {q.l}
                                </button>
                            ))}
                        </div>
                    </FieldGroup>
                    <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0' }}>
                        <i className="bi bi-info-circle" style={{ marginRight: 4 }} />
                        A mensagem será enviada pela instância WhatsApp conectada do tenant.
                    </p>
                </>
            )}

            {data.type === 'create_task' && (
                <>
                    <FieldGroup label="Assunto da tarefa">
                        <input style={field.input} value={data.subject || ''} onChange={e => update('subject', e.target.value)} placeholder="Ex: Ligar para o lead" />
                    </FieldGroup>
                    <FieldGroup label="Descrição (opcional)">
                        <textarea style={{ ...field.input, height: 60, resize: 'vertical' }} value={data.description || ''} onChange={e => update('description', e.target.value)} placeholder="Detalhes da tarefa..." />
                    </FieldGroup>
                    <FieldGroup label="Prazo (dias a partir de hoje)">
                        <input type="number" style={field.input} min={0} max={365} value={data.due_date_offset ?? 1} onChange={e => update('due_date_offset', parseInt(e.target.value) || 1)} />
                    </FieldGroup>
                    <FieldGroup label="Atribuir a">
                        <select style={field.input} value={data.assigned_to_mode || 'lead_owner'} onChange={e => update('assigned_to_mode', e.target.value)}>
                            <option value="lead_owner">Responsável do lead</option>
                            <option value="specific">Usuário específico</option>
                        </select>
                    </FieldGroup>
                    {data.assigned_to_mode === 'specific' && users.length > 0 && (
                        <FieldGroup label="Usuário">
                            <select style={field.input} value={data.assigned_to_user_id || ''} onChange={e => update('assigned_to_user_id', e.target.value)}>
                                <option value="">— Selecione —</option>
                                {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </FieldGroup>
                    )}
                </>
            )}

            {data.type === 'redirect' && (
                <>
                    <FieldGroup label="URL de redirecionamento">
                        <input style={field.input} value={data.url || ''} onChange={e => update('url', e.target.value)} placeholder="https://exemplo.com/pagina" />
                    </FieldGroup>
                    <FieldGroup label="Abrir em">
                        <select style={field.input} value={data.target || '_blank'} onChange={e => update('target', e.target.value)}>
                            <option value="_blank">Nova aba</option>
                            <option value="_self">Mesma aba</option>
                        </select>
                    </FieldGroup>
                    <p style={{ fontSize: 11, color: '#9ca3af', margin: '4px 0 0' }}>
                        <i className="bi bi-info-circle" style={{ marginRight: 4 }} />
                        Disponível apenas para chatbots do Website.
                    </p>
                </>
            )}
        </>
    );
}

function DelayForm({ data, update }) {
    const FONT    = "'Inter', system-ui, sans-serif";
    const seconds = data.seconds ?? 3;

    return (
        <>
            <FieldGroup label="Duração (segundos)">
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <input
                        type="range"
                        min={1}
                        max={30}
                        value={seconds}
                        onChange={e => update('seconds', parseInt(e.target.value, 10))}
                        style={{ flex: 1, accentColor: '#f59e0b' }}
                    />
                    <span style={{
                        minWidth: 52, textAlign: 'center',
                        fontWeight: 700, fontSize: 15, color: '#92400e',
                        background: '#fffbeb', border: '1px solid #fde68a',
                        borderRadius: 7, padding: '3px 8px', fontFamily: FONT,
                    }}>
                        {seconds}s
                    </span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 10, color: '#9ca3af', marginTop: 3 }}>
                    <span>1s</span><span>30s</span>
                </div>
            </FieldGroup>
            <div style={{ padding: '8px 10px', background: '#fffbeb', border: '1px solid #fde68a', borderRadius: 7, fontSize: 11, color: '#92400e', fontFamily: FONT }}>
                <i className="bi bi-info-circle" style={{ marginRight: 5 }} />
                O bot aguardará <strong>{seconds} segundo{seconds !== 1 ? 's' : ''}</strong> antes de enviar a próxima mensagem, simulando digitação.
            </div>
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

function NodePanel({ node, onUpdate, onDelete, variables, pipelines, tags, users, customFieldDefs }) {
    const [data, setData] = useState(node.data);
    const cfg = NODE_TYPES_CONFIG[node.type] || NODE_TYPES_CONFIG.message;
    const textareaRef  = useRef(null);
    const lastCursor   = useRef({ field: 'text', start: 0, end: 0 });

    useEffect(() => { setData(node.data); }, [node.id]);

    const update = (f, v) => {
        const nd = (typeof f === 'object' && f !== null && !Array.isArray(f))
            ? { ...data, ...f }
            : { ...data, [f]: v };
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

            {/* Delete button — not shown for start node */}
            {node.type !== 'start' && (
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
            )}

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
                {node.type === 'action'    && <ActionForm data={data} update={update} pipelines={pipelines} allVars={allVars} tags={tags} users={users} customFieldDefs={customFieldDefs} />}
                {node.type === 'delay'     && <DelayForm data={data} update={update} />}
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
                            {SYSTEM_VARS_META.map(v => (
                                <button
                                    key={v.key}
                                    onClick={() => insertVar(v.key)}
                                    title={v.key}
                                    style={{
                                        background: '#faf5ff', border: '1px solid #e9d5ff',
                                        color: '#6d28d9', borderRadius: 5, padding: '2px 7px',
                                        fontSize: 11, fontFamily: "'Inter', sans-serif",
                                        cursor: 'pointer', fontWeight: 500,
                                    }}
                                >
                                    {v.label}
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
                    {SYSTEM_VARS_META.map(v => (
                        <div key={v.key} title={v.key} style={{
                            fontSize: 11, color: '#7c3aed',
                            background: '#faf5ff', border: '1px solid #ede9fe',
                            borderRadius: 5, padding: '3px 8px', marginBottom: 4,
                            display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 6,
                        }}>
                            <span style={{ fontWeight: 600 }}>{v.label}</span>
                            <span style={{ fontFamily: 'monospace', fontSize: 10, color: '#9ca3af' }}>{v.key}</span>
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

// ── Edge: deletável com botão X ───────────────────────────────────────────────

function DeletableEdge({
    id, sourceX, sourceY, targetX, targetY,
    sourcePosition, targetPosition, selected,
}) {
    const { setEdges } = useReactFlow();
    const [hovered, setHovered] = useState(false);

    const [edgePath, labelX, labelY] = getSmoothStepPath({
        sourceX, sourceY, sourcePosition,
        targetX, targetY, targetPosition,
    });

    const showBtn = selected || hovered;

    return (
        <>
            {/* Área invisível mais larga para facilitar hover/clique */}
            <path
                d={edgePath}
                fill="none"
                stroke="transparent"
                strokeWidth={16}
                onMouseEnter={() => setHovered(true)}
                onMouseLeave={() => setHovered(false)}
                style={{ cursor: 'pointer' }}
            />
            <BaseEdge
                path={edgePath}
                style={{
                    stroke: showBtn ? '#f97316' : BLUE,
                    strokeWidth: showBtn ? 2.5 : 2,
                    transition: 'stroke 0.15s',
                }}
                markerEnd={`url(#arrow-${showBtn ? 'hover' : 'default'})`}
            />
            <EdgeLabelRenderer>
                <div
                    style={{
                        position: 'absolute',
                        transform: `translate(-50%, -50%) translate(${labelX}px, ${labelY}px)`,
                        pointerEvents: 'all',
                        opacity: showBtn ? 1 : 0,
                        transition: 'opacity 0.15s',
                    }}
                    onMouseEnter={() => setHovered(true)}
                    onMouseLeave={() => setHovered(false)}
                >
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            setEdges(eds => eds.filter(ed => ed.id !== id));
                        }}
                        title="Remover conexão"
                        style={{
                            width: 20, height: 20,
                            borderRadius: '50%',
                            background: '#ef4444',
                            border: '2px solid #fff',
                            color: '#fff',
                            fontSize: 12, fontWeight: 700,
                            lineHeight: 1,
                            cursor: 'pointer',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            boxShadow: '0 1px 4px rgba(0,0,0,0.25)',
                            padding: 0,
                        }}
                    >
                        ×
                    </button>
                </div>
            </EdgeLabelRenderer>
        </>
    );
}

const edgeTypes = { deletable: DeletableEdge };

// ── Edge defaults ─────────────────────────────────────────────────────────────

const defaultEdgeOptions = {
    type: 'deletable',
    animated: true,
    style: { stroke: BLUE, strokeWidth: 2 },
    markerEnd: { type: MarkerType.ArrowClosed, color: BLUE, width: 14, height: 14 },
};

// ── Node Sidebar (Typebot-style) ───────────────────────────────────────────────

function NodeSidebar({ onAddNode, onDragStart }) {
    const FONT = "'Inter', system-ui, sans-serif";
    const channel = (window.chatbotBuilderData?.flow?.channel) || 'whatsapp';

    const channelLabel = { whatsapp: 'WhatsApp', instagram: 'Instagram', website: 'Website' }[channel] || channel;
    const channelColor = { whatsapp: '#25d366', instagram: '#e1306c', website: '#2563eb' }[channel] || '#2563eb';
    const channelIcon  = { whatsapp: 'whatsapp', instagram: 'instagram', website: 'globe' }[channel] || 'chat-dots';

    const messageNodes = [
        { type: 'message', label: 'Enviar mensagem', icon: channelIcon, color: channelColor },
        { type: 'input',   label: 'Pergunta',        icon: 'keyboard',  color: channelColor },
    ];

    const logicNodes = [
        { type: 'condition', label: 'Condição', icon: 'diagram-2',     color: '#ea580c' },
        { type: 'delay',     label: 'Atraso',   icon: 'clock-history',  color: '#ef4444' },
        { type: 'end',       label: 'Fim',      icon: 'stop-circle',    color: '#6b7280' },
    ];

    const actionNodes = [
        { type: 'action', label: 'Ação', icon: 'lightning-charge', color: '#f59e0b' },
    ];

    const SidebarItem = ({ type, label, icon, color }) => (
        <div
            draggable
            onDragStart={(e) => onDragStart(e, type)}
            onClick={() => onAddNode(type)}
            title={`Adicionar ${label}`}
            style={{
                display: 'flex', alignItems: 'center', gap: 8,
                padding: '7px 10px', marginBottom: 4,
                background: '#fff', border: '1px solid #e8eaf0',
                borderRadius: 8, cursor: 'grab',
                fontSize: 12, fontWeight: 600, color: '#374151',
                userSelect: 'none', fontFamily: FONT,
                transition: 'background .12s, border-color .12s',
            }}
            onMouseEnter={e => { e.currentTarget.style.background = '#f8fafc'; e.currentTarget.style.borderColor = color + '60'; }}
            onMouseLeave={e => { e.currentTarget.style.background = '#fff'; e.currentTarget.style.borderColor = '#e8eaf0'; }}
        >
            <div style={{
                width: 26, height: 26, borderRadius: 7,
                background: color + '14',
                display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
            }}>
                <i className={`bi bi-${icon}`} style={{ fontSize: 12, color }} />
            </div>
            {label}
        </div>
    );

    const SectionTitle = ({ children }) => (
        <div style={{ fontSize: 9, fontWeight: 700, color: '#b0b8c4', textTransform: 'uppercase', letterSpacing: '0.08em', padding: '10px 4px 5px', fontFamily: FONT }}>
            {children}
        </div>
    );

    return (
        <div style={{
            width: 190, flexShrink: 0,
            borderRight: '1px solid #e5e7eb',
            background: '#fafbfc',
            display: 'flex', flexDirection: 'column',
            zIndex: 10,
        }}>
            <div style={{
                padding: '10px 12px',
                borderBottom: '1px solid #e5e7eb',
                display: 'flex', alignItems: 'center', gap: 6,
            }}>
                <i className={`bi bi-${channelIcon}`} style={{ fontSize: 14, color: channelColor }} />
                <div>
                    <div style={{ fontSize: 11, fontWeight: 700, color: '#1a1d23', fontFamily: FONT }}>{channelLabel}</div>
                    <div style={{ fontSize: 9, color: '#9ca3af', fontFamily: FONT }}>Arraste ou clique</div>
                </div>
            </div>
            <div style={{ flex: 1, overflowY: 'auto', padding: '4px 8px 12px' }}>
                <SectionTitle>Mensagens</SectionTitle>
                {messageNodes.map(n => <SidebarItem key={n.type} {...n} />)}

                <SectionTitle>Lógica</SectionTitle>
                {logicNodes.map(n => <SidebarItem key={n.type} {...n} />)}

                <SectionTitle>Ações</SectionTitle>
                {actionNodes.map(n => <SidebarItem key={n.type} {...n} />)}
            </div>
        </div>
    );
}

// ── Bot Templates ─────────────────────────────────────────────────────────────

const BOT_TEMPLATES = [
    {
        id: 'lead_capture',
        name: 'Captura de Lead',
        description: 'Coleta nome, email e telefone. Cria lead automaticamente.',
        icon: 'bi-person-plus',
        nodes: [
            { id: 't1', type: 'message', position: { x: 250, y: 0 }, data: { text: 'Olá! Tudo bem? 👋\nVamos começar!' } },
            { id: 't2', type: 'input', position: { x: 250, y: 120 }, data: { text: 'Qual é o seu nome?', variable: 'nome', input_type: 'text' } },
            { id: 't3', type: 'input', position: { x: 250, y: 240 }, data: { text: 'E o seu e-mail?', variable: 'email', input_type: 'email' } },
            { id: 't4', type: 'input', position: { x: 250, y: 360 }, data: { text: 'Agora informe um número de telefone:', variable: 'telefone', input_type: 'phone' } },
            { id: 't5', type: 'action', position: { x: 250, y: 480 }, data: { type: 'create_lead', name_var: 'nome', email_var: 'email', phone_var: 'telefone' } },
            { id: 't6', type: 'message', position: { x: 250, y: 600 }, data: { text: 'Obrigado, {{nome}}! Entraremos em contato em breve. 🚀' } },
            { id: 't7', type: 'end', position: { x: 250, y: 720 }, data: {} },
        ],
        edges: [
            { id: 'te1', source: 't1', sourceHandle: 'default', target: 't2' },
            { id: 'te2', source: 't2', sourceHandle: 'default', target: 't3' },
            { id: 'te3', source: 't3', sourceHandle: 'default', target: 't4' },
            { id: 'te4', source: 't4', sourceHandle: 'default', target: 't5' },
            { id: 'te5', source: 't5', sourceHandle: 'default', target: 't6' },
            { id: 'te6', source: 't6', sourceHandle: 'default', target: 't7' },
        ],
    },
    {
        id: 'real_estate',
        name: 'Imobiliária',
        description: 'Qualificação: tipo de imóvel, faixa de preço e localização.',
        icon: 'bi-building',
        nodes: [
            { id: 't1', type: 'message', position: { x: 250, y: 0 }, data: { text: 'Olá! Vou ajudar você a encontrar o imóvel ideal. 🏠' } },
            { id: 't2', type: 'input', position: { x: 250, y: 120 }, data: { text: 'Qual é o seu nome?', variable: 'nome', input_type: 'text' } },
            { id: 't3', type: 'input', position: { x: 250, y: 240 }, data: { text: 'Você procura comprar ou alugar?', variable: 'tipo', input_type: 'text', buttons: ['Comprar', 'Alugar'] } },
            { id: 't4', type: 'input', position: { x: 250, y: 360 }, data: { text: 'Qual a região de interesse?', variable: 'regiao', input_type: 'text' } },
            { id: 't5', type: 'input', position: { x: 250, y: 480 }, data: { text: 'Qual a faixa de valor?', variable: 'faixa_valor', input_type: 'text', buttons: ['Até 300mil', '300-600mil', '600mil-1M', 'Acima de 1M'] } },
            { id: 't6', type: 'input', position: { x: 250, y: 600 }, data: { text: 'Informe seu e-mail para contato:', variable: 'email', input_type: 'email' } },
            { id: 't7', type: 'input', position: { x: 250, y: 720 }, data: { text: 'E seu telefone com DDD:', variable: 'telefone', input_type: 'phone' } },
            { id: 't8', type: 'action', position: { x: 250, y: 840 }, data: { type: 'create_lead', name_var: 'nome', email_var: 'email', phone_var: 'telefone' } },
            { id: 't9', type: 'message', position: { x: 250, y: 960 }, data: { text: 'Perfeito, {{nome}}! Um corretor entrará em contato. 🔑' } },
            { id: 't10', type: 'end', position: { x: 250, y: 1080 }, data: {} },
        ],
        edges: [
            { id: 'te1', source: 't1', sourceHandle: 'default', target: 't2' },
            { id: 'te2', source: 't2', sourceHandle: 'default', target: 't3' },
            { id: 'te3', source: 't3', sourceHandle: 'default', target: 't4' },
            { id: 'te4', source: 't4', sourceHandle: 'default', target: 't5' },
            { id: 'te5', source: 't5', sourceHandle: 'default', target: 't6' },
            { id: 'te6', source: 't6', sourceHandle: 'default', target: 't7' },
            { id: 'te7', source: 't7', sourceHandle: 'default', target: 't8' },
            { id: 'te8', source: 't8', sourceHandle: 'default', target: 't9' },
            { id: 'te9', source: 't9', sourceHandle: 'default', target: 't10' },
        ],
    },
    {
        id: 'ecommerce',
        name: 'E-commerce',
        description: 'Atendimento: dúvida sobre pedido, troca ou suporte.',
        icon: 'bi-cart3',
        nodes: [
            { id: 't1', type: 'message', position: { x: 250, y: 0 }, data: { text: 'Olá! Como posso ajudar? 🛒' } },
            { id: 't2', type: 'input', position: { x: 250, y: 120 }, data: { text: 'Qual o motivo do contato?', variable: 'motivo', input_type: 'text', buttons: ['Dúvida sobre pedido', 'Troca/Devolução', 'Suporte técnico', 'Outro'] } },
            { id: 't3', type: 'input', position: { x: 250, y: 240 }, data: { text: 'Qual é o seu nome?', variable: 'nome', input_type: 'text' } },
            { id: 't4', type: 'input', position: { x: 250, y: 360 }, data: { text: 'Informe seu e-mail:', variable: 'email', input_type: 'email' } },
            { id: 't5', type: 'input', position: { x: 250, y: 480 }, data: { text: 'Descreva brevemente sua solicitação:', variable: 'descricao', input_type: 'text' } },
            { id: 't6', type: 'action', position: { x: 250, y: 600 }, data: { type: 'create_lead', name_var: 'nome', email_var: 'email', phone_var: '' } },
            { id: 't7', type: 'message', position: { x: 250, y: 720 }, data: { text: 'Recebemos sua solicitação, {{nome}}! Nossa equipe vai responder em breve. 📦' } },
            { id: 't8', type: 'end', position: { x: 250, y: 840 }, data: {} },
        ],
        edges: [
            { id: 'te1', source: 't1', sourceHandle: 'default', target: 't2' },
            { id: 'te2', source: 't2', sourceHandle: 'default', target: 't3' },
            { id: 'te3', source: 't3', sourceHandle: 'default', target: 't4' },
            { id: 'te4', source: 't4', sourceHandle: 'default', target: 't5' },
            { id: 'te5', source: 't5', sourceHandle: 'default', target: 't6' },
            { id: 'te6', source: 't6', sourceHandle: 'default', target: 't7' },
            { id: 'te7', source: 't7', sourceHandle: 'default', target: 't8' },
        ],
    },
    {
        id: 'clinic',
        name: 'Clínica / Saúde',
        description: 'Agendamento: especialidade, convênio e horário.',
        icon: 'bi-heart-pulse',
        nodes: [
            { id: 't1', type: 'message', position: { x: 250, y: 0 }, data: { text: 'Olá! Bem-vindo à nossa clínica. 🩺' } },
            { id: 't2', type: 'input', position: { x: 250, y: 120 }, data: { text: 'Qual é o seu nome?', variable: 'nome', input_type: 'text' } },
            { id: 't3', type: 'input', position: { x: 250, y: 240 }, data: { text: 'Qual especialidade você procura?', variable: 'especialidade', input_type: 'text', buttons: ['Clínico Geral', 'Dermatologia', 'Ortopedia', 'Outro'] } },
            { id: 't4', type: 'input', position: { x: 250, y: 360 }, data: { text: 'Possui convênio?', variable: 'convenio', input_type: 'text', buttons: ['Sim', 'Não / Particular'] } },
            { id: 't5', type: 'input', position: { x: 250, y: 480 }, data: { text: 'Informe seu telefone com DDD:', variable: 'telefone', input_type: 'phone' } },
            { id: 't6', type: 'input', position: { x: 250, y: 600 }, data: { text: 'E seu e-mail:', variable: 'email', input_type: 'email' } },
            { id: 't7', type: 'action', position: { x: 250, y: 720 }, data: { type: 'create_lead', name_var: 'nome', email_var: 'email', phone_var: 'telefone' } },
            { id: 't8', type: 'message', position: { x: 250, y: 840 }, data: { text: 'Obrigado, {{nome}}! Vamos entrar em contato para confirmar o agendamento. 📅' } },
            { id: 't9', type: 'end', position: { x: 250, y: 960 }, data: {} },
        ],
        edges: [
            { id: 'te1', source: 't1', sourceHandle: 'default', target: 't2' },
            { id: 'te2', source: 't2', sourceHandle: 'default', target: 't3' },
            { id: 'te3', source: 't3', sourceHandle: 'default', target: 't4' },
            { id: 'te4', source: 't4', sourceHandle: 'default', target: 't5' },
            { id: 'te5', source: 't5', sourceHandle: 'default', target: 't6' },
            { id: 'te6', source: 't6', sourceHandle: 'default', target: 't7' },
            { id: 'te7', source: 't7', sourceHandle: 'default', target: 't8' },
            { id: 'te8', source: 't8', sourceHandle: 'default', target: 't9' },
        ],
    },
    {
        id: 'saas_trial',
        name: 'SaaS / Teste Grátis',
        description: 'Onboarding: coleta nome, email, telefone e empresa.',
        icon: 'bi-rocket-takeoff',
        nodes: [
            { id: 't1', type: 'message', position: { x: 250, y: 0 }, data: { text: 'Olá! Quer testar nossa plataforma gratuitamente? 🚀' } },
            { id: 't2', type: 'input', position: { x: 250, y: 120 }, data: { text: 'Qual é o seu nome?', variable: 'nome', input_type: 'text' } },
            { id: 't3', type: 'input', position: { x: 250, y: 240 }, data: { text: 'E o seu e-mail?', variable: 'email', input_type: 'email' } },
            { id: 't4', type: 'input', position: { x: 250, y: 360 }, data: { text: 'Informe seu telefone:', variable: 'telefone', input_type: 'phone' } },
            { id: 't5', type: 'input', position: { x: 250, y: 480 }, data: { text: 'Nome da sua empresa:', variable: 'empresa', input_type: 'text' } },
            { id: 't6', type: 'action', position: { x: 250, y: 600 }, data: { type: 'create_lead', name_var: 'nome', email_var: 'email', phone_var: 'telefone' } },
            { id: 't7', type: 'message', position: { x: 250, y: 720 }, data: { text: 'Perfeito, {{nome}}! Sua conta trial será criada e enviaremos os dados de acesso por e-mail. ✨' } },
            { id: 't8', type: 'end', position: { x: 250, y: 840 }, data: {} },
        ],
        edges: [
            { id: 'te1', source: 't1', sourceHandle: 'default', target: 't2' },
            { id: 'te2', source: 't2', sourceHandle: 'default', target: 't3' },
            { id: 'te3', source: 't3', sourceHandle: 'default', target: 't4' },
            { id: 'te4', source: 't4', sourceHandle: 'default', target: 't5' },
            { id: 'te5', source: 't5', sourceHandle: 'default', target: 't6' },
            { id: 'te6', source: 't6', sourceHandle: 'default', target: 't7' },
            { id: 'te7', source: 't7', sourceHandle: 'default', target: 't8' },
        ],
    },
];

function TemplatePickerModal({ onSelect, onClose }) {
    return (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center' }} onClick={onClose}>
            <div style={{ background: '#fff', borderRadius: 14, padding: 24, maxWidth: 640, width: '95%', maxHeight: '80vh', overflowY: 'auto', boxShadow: '0 20px 60px rgba(0,0,0,0.2)' }} onClick={e => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 }}>
                    <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: '#1a1d23' }}>Modelos de Bot</h3>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: 18, cursor: 'pointer', color: '#6b7280' }}>&times;</button>
                </div>
                <p style={{ fontSize: 13, color: '#6b7280', margin: '0 0 16px' }}>Selecione um modelo para carregar no canvas. Os nós atuais serão substituídos.</p>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))', gap: 12 }}>
                    {BOT_TEMPLATES.map(t => (
                        <div
                            key={t.id}
                            onClick={() => onSelect(t)}
                            style={{
                                border: '1.5px solid #e8eaf0', borderRadius: 10, padding: 16, cursor: 'pointer',
                                transition: 'border-color .15s, box-shadow .15s',
                            }}
                            onMouseEnter={e => { e.currentTarget.style.borderColor = '#2563eb'; e.currentTarget.style.boxShadow = '0 2px 8px rgba(37,99,235,0.12)'; }}
                            onMouseLeave={e => { e.currentTarget.style.borderColor = '#e8eaf0'; e.currentTarget.style.boxShadow = 'none'; }}
                        >
                            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                                <i className={`bi ${t.icon}`} style={{ fontSize: 20, color: '#2563eb' }} />
                                <span style={{ fontWeight: 700, fontSize: 13, color: '#1a1d23' }}>{t.name}</span>
                            </div>
                            <p style={{ fontSize: 12, color: '#6b7280', margin: 0, lineHeight: 1.4 }}>{t.description}</p>
                            <div style={{ marginTop: 8, fontSize: 11, color: '#9ca3af' }}>
                                {t.nodes.length} nós
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ── Main Builder ──────────────────────────────────────────────────────────────

function ChatbotBuilder() {
    const cfg = window.chatbotBuilderData || {};
    const { screenToFlowPosition } = useReactFlow();

    const initialNodes = (cfg.nodes || []).map(n => ({ ...n, type: n.type || 'message' }));
    const initialEdges = (cfg.edges || []).map(e => ({
        ...e,
        type: 'deletable',
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
    const tags            = cfg.tags            || [];
    const users           = cfg.users           || [];
    const customFieldDefs = cfg.customFieldDefs || [];
    const [isActive, setIsActive] = useState(!!cfg.flow?.is_active);
    const [saving, setSaving] = useState(false);
    const [saveMsg, setSaveMsg] = useState('');
    const [showVars, setShowVars] = useState(false);
    const [showTrigger, setShowTrigger] = useState(false);
    const [showTemplates, setShowTemplates] = useState(false);

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
        setNodes(ns => {
            const node = ns.find(n => n.id === nodeId);
            if (node?.type === 'start') return ns; // start node cannot be deleted
            return ns.filter(n => n.id !== nodeId);
        });
        setEdges(es => es.filter(e => e.source !== nodeId && e.target !== nodeId));
        setSelectedNode(null);
    }, [setNodes, setEdges]);

    // Intercept keyboard Delete to protect start node
    const handleNodesChange = useCallback((changes) => {
        const filtered = changes.filter(c => {
            if (c.type === 'remove') {
                const node = nodes.find(n => n.id === c.id);
                return node?.type !== 'start';
            }
            return true;
        });
        onNodesChange(filtered);
    }, [nodes, onNodesChange]);

    // Drag & drop from sidebar to canvas
    const onDragStart = useCallback((e, type) => {
        e.dataTransfer.setData('node-type', type);
        e.dataTransfer.effectAllowed = 'copy';
    }, []);

    const onDrop = useCallback((e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('node-type');
        if (!type) return;
        const position = screenToFlowPosition({ x: e.clientX, y: e.clientY });
        const id = genId();
        setNodes(ns => [...ns, { id, type, position, data: { label: '' } }]);
    }, [screenToFlowPosition, setNodes]);

    const onDragOver = useCallback((e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }, []);

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
            if (res.ok) {
                const json = await res.json();
                // Atualizar IDs temporários (string) pelos IDs reais do banco (numéricos)
                if (json.idMap && Object.keys(json.idMap).length > 0) {
                    setNodes(ns => ns.map(n => ({ ...n, id: String(json.idMap[n.id] ?? n.id) })));
                    setEdges(es => es.map(e => ({
                        ...e,
                        source: String(json.idMap[e.source] ?? e.source),
                        target: String(json.idMap[e.target] ?? e.target),
                    })));
                }
                setSaveMsg('Salvo');
            } else {
                setSaveMsg('Erro ao salvar');
            }
        } catch {
            setSaveMsg('Erro de conexão');
        } finally {
            setSaving(false);
            setTimeout(() => setSaveMsg(''), 3000);
        }
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100%', background: '#f8fafc' }}>

            {/* ── Top bar (slim — flow controls only) ── */}
            <div style={{
                flexShrink: 0,
                display: 'flex', gap: 6, alignItems: 'center', flexWrap: 'nowrap',
                padding: '8px 14px',
                background: '#fff',
                borderBottom: '1px solid #e5e7eb',
                boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
                overflowX: 'auto',
            }}>
                <div style={{ width: 1, height: 0, flexShrink: 0 }} />

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

                {/* Templates button */}
                <button
                    onClick={() => setShowTemplates(true)}
                    style={{
                        ...field.smallBtn,
                        background: '#f0fdf4',
                        color: '#15803d',
                        border: '1px solid #bbf7d0',
                        display: 'flex', alignItems: 'center', gap: 5, flexShrink: 0,
                    }}
                >
                    <i className="bi bi-lightning" style={{ fontSize: 11 }} />
                    Modelos
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

                {/* Left: node sidebar (always visible) + optional trigger/vars panels */}
                <NodeSidebar onAddNode={addNode} onDragStart={onDragStart} />

                {/* Left: trigger panel (opens next to sidebar) */}
                {showTrigger && (
                    <TriggerPanel keywords={triggerKeywords} setKeywords={setTriggerKeywords} onClose={() => setShowTrigger(false)} />
                )}

                {/* Left: variables panel */}
                {showVars && (
                    <VariablesPanel variables={variables} setVariables={setVariables} onClose={() => setShowVars(false)} />
                )}

                {/* Center: canvas */}
                <div
                    style={{ flex: 1, position: 'relative', overflow: 'hidden' }}
                    onDrop={onDrop}
                    onDragOver={onDragOver}
                >
                    <ReactFlow
                        nodes={displayNodes}
                        edges={edges}
                        onNodesChange={handleNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        nodeTypes={nodeTypes}
                        edgeTypes={edgeTypes}
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
                            users={users}
                            customFieldDefs={customFieldDefs}
                        />
                    </div>
                )}
            </div>

            {showTemplates && (
                <TemplatePickerModal
                    onClose={() => setShowTemplates(false)}
                    onSelect={(template) => {
                        setNodes(template.nodes.map(n => ({ ...n, id: String(n.id) })));
                        setEdges(template.edges.map(e => ({ ...e, id: String(e.id), source: String(e.source), target: String(e.target) })));
                        setShowTemplates(false);
                    }}
                />
            )}
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
