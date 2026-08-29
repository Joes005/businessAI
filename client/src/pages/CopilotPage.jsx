import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Sparkles,
  Send,
  Mic,
  MicOff,
  Bot,
  User,
  ArrowRight,
  TrendingUp,
  AlertTriangle,
  CheckCircle2,
  HelpCircle,
  ShieldAlert,
  Check,
  X,
  Target,
  Sun,
  ListTodo,
} from 'lucide-react';
import Card from '../components/ui/Card';
import Button from '../components/ui/Button';
import Badge from '../components/ui/Badge';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../components/ui/ToastContext';
import apiService from '../services/api';
import './CopilotPage.css';

export default function CopilotPage() {
  const { currentBusiness } = useAuth();
  const navigate = useNavigate();
  const toast = useToast();
  const chatBottomRef = useRef(null);

  const currencySymbol = currentBusiness?.currency === 'USD' ? '$' : currentBusiness?.currency === 'EUR' ? '€' : '₹';

  // Proactive Insights, Briefing, Goals & Active Task State
  const [proactiveInsights, setProactiveInsights] = useState([]);
  const [dailyBriefing, setDailyBriefing] = useState(null);
  const [goals, setGoals] = useState([]);
  const [activeTask, setActiveTask] = useState(null);
  const [pendingAction, setPendingAction] = useState(null);

  // Chat Thread State
  const [messages, setMessages] = useState([
    {
      id: 1,
      sender: 'copilot',
      text: `Hello! I am your **AI Business Employee** for **${currentBusiness?.name || 'your store'}**.\n\nI analyze your sales, monitor stockout risk based on sales velocity, track uncollected customer debt, generate daily business briefings, and execute multi-step business plans.\n\nHow can I assist your business today?`,
      metrics: [],
      suggested_actions: [
        { label: 'How much did I sell today?' },
        { label: 'Who owes me money?' },
        { label: 'Which items are low in stock?' },
        { label: 'What is my profit this month?' },
        { label: 'innaiku sales evlo?' },
      ],
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    },
  ]);

  const [inputPrompt, setInputPrompt] = useState('');
  const [loading, setLoading] = useState(false);
  const [isListening, setIsListening] = useState(false);

  // Fetch Copilot 2.0 Data (Insights, Briefing, Goals)
  const fetchCopilotData = useCallback(async () => {
    try {
      const [insightsRes, briefingRes, goalsRes] = await Promise.allSettled([
        apiService.copilot.getInsights(),
        apiService.copilot.getBriefing(),
        apiService.copilot.getGoals(),
      ]);

      if (insightsRes.status === 'fulfilled' && insightsRes.value.success) {
        setProactiveInsights(insightsRes.value.data.insights || []);
      }
      if (briefingRes.status === 'fulfilled' && briefingRes.value.success) {
        setDailyBriefing(briefingRes.value.data);
      }
      if (goalsRes.status === 'fulfilled' && goalsRes.value.success) {
        setGoals(goalsRes.value.data.goals || []);
      }
    } catch (err) {
      console.error('Failed to load copilot 2.0 data:', err);
    }
  }, []);

  useEffect(() => {
    fetchCopilotData();
  }, [fetchCopilotData]);

  // Scroll to Bottom of Chat
  useEffect(() => {
    chatBottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, pendingAction, activeTask]);

  // Send Message Handler
  const handleSendMessage = async (textToSend) => {
    const prompt = textToSend || inputPrompt;
    if (!prompt.trim() || loading) return;

    // Add User Message
    const userMessage = {
      id: Date.now(),
      sender: 'user',
      text: prompt,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };

    setMessages((prev) => [...prev, userMessage]);
    setInputPrompt('');
    setLoading(true);

    try {
      const res = await apiService.copilot.chat(prompt);
      if (res.success && res.data) {
        const aiMessage = {
          id: Date.now() + 1,
          sender: 'copilot',
          text: res.data.answer,
          metrics: res.data.metrics || [],
          dataType: res.data.data_type,
          data: res.data.data,
          suggested_actions: res.data.suggested_actions || [],
          requires_action: res.data.requires_action,
          action_id: res.data.action_id,
          timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };

        if (res.data.requires_action && res.data.action_id) {
          setPendingAction({
            action_id: res.data.action_id,
            description: res.data.answer,
          });
        }

        setMessages((prev) => [...prev, aiMessage]);
      }
    } catch (err) {
      toast.error('Copilot encountered an issue processing your query.');
    } finally {
      setLoading(false);
    }
  };

  // Action Confirmation Approval / Rejection Handler
  const handleConfirmAction = async (approved) => {
    if (!pendingAction) return;

    try {
      const res = await apiService.copilot.confirmAction({
        action_id: pendingAction.action_id,
        approved,
      });

      if (res.success) {
        toast.success(res.message);
        setPendingAction(null);

        const statusMsg = {
          id: Date.now(),
          sender: 'copilot',
          text: approved ? `✅ **Action Approved & Executed**: ${res.message}` : `❌ **Action Cancelled** by user.`,
          timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        };
        setMessages((prev) => [...prev, statusMsg]);
      }
    } catch (err) {
      toast.error('Failed to confirm action.');
    }
  };

  // Quick Prompt Pill Handler
  const handlePillClick = (promptText, route) => {
    if (route) {
      navigate(route);
      return;
    }
    handleSendMessage(promptText);
  };

  // Speech Recognition Trigger
  const toggleVoiceRecognition = () => {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
      toast.error('Voice recognition is not supported in this browser. Try Chrome/Edge.');
      return;
    }

    if (isListening) {
      setIsListening(false);
      return;
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = 'en-US';
    recognition.interimResults = false;

    recognition.onstart = () => {
      setIsListening(true);
      toast.success('Listening... Speak your question now.');
    };

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      setInputPrompt(transcript);
      setIsListening(false);
      handleSendMessage(transcript);
    };

    recognition.onerror = () => {
      setIsListening(false);
      toast.error('Speech recognition error.');
    };

    recognition.onend = () => {
      setIsListening(false);
    };

    recognition.start();
  };

  return (
    <div className="copilot-page animate-fade-in">
      {/* Daily Briefing Header Widget */}
      {dailyBriefing && (
        <div className="daily-briefing-card glass-card">
          <div className="briefing-header">
            <div className="briefing-title">
              <Sun size={20} className="briefing-sun-icon" />
              <strong>Morning Business Briefing — {dailyBriefing.date}</strong>
            </div>
            <Badge variant="primary">AI Employee Active</Badge>
          </div>
          <p className="briefing-text">{dailyBriefing.briefing_text}</p>
          {dailyBriefing.top_priorities && dailyBriefing.top_priorities.length > 0 && (
            <div className="briefing-priorities">
              <span>🎯 Top Priorities Today:</span>
              <ul>
                {dailyBriefing.top_priorities.map((item, idx) => (
                  <li key={idx}>{item}</li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}

      {/* Proactive Health Insights Bar */}
      {proactiveInsights.length > 0 && (
        <div className="proactive-insights-bar">
          <span className="insights-label">💡 Proactive Alert:</span>
          <div className="insights-chips">
            {proactiveInsights.map((item, idx) => (
              <button key={idx} className="insight-chip" onClick={() => navigate(item.route)}>
                <span>{item.title}: {item.problem}</span>
                <ArrowRight size={12} />
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Action Confirmation Modal Banner */}
      {pendingAction && (
        <div className="action-confirmation-banner glass-card">
          <div className="confirmation-content">
            <ShieldAlert size={20} className="confirmation-icon" />
            <div>
              <strong>Action Confirmation Required (High Risk)</strong>
              <p>{pendingAction.description}</p>
            </div>
          </div>
          <div className="confirmation-btn-group">
            <Button variant="outline" size="sm" icon={X} onClick={() => handleConfirmAction(false)}>
              Reject / Cancel
            </Button>
            <Button variant="primary" size="sm" icon={Check} onClick={() => handleConfirmAction(true)}>
              Approve & Execute
            </Button>
          </div>
        </div>
      )}

      {/* Main Chat Window Card */}
      <Card className="chat-card glass-card" padding="compact">
        {/* Messages Stream Container */}
        <div className="chat-messages-container">
          {messages.map((msg) => (
            <div key={msg.id} className={`chat-message-row message-${msg.sender}`}>
              <div className="message-avatar">
                {msg.sender === 'copilot' ? <Bot size={18} /> : <User size={18} />}
              </div>

              <div className="message-bubble-wrapper">
                <div className="message-bubble">
                  {/* Message Text with formatting */}
                  <div className="message-text">
                    {msg.text.split('\n\n').map((para, i) => (
                      <p key={i} dangerouslySetInnerHTML={{ __html: para.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') }} />
                    ))}
                  </div>

                  {/* Metrics Chips */}
                  {msg.metrics && msg.metrics.length > 0 && (
                    <div className="message-metrics-grid">
                      {msg.metrics.map((m, i) => (
                        <div key={i} className="metric-chip">
                          <span className="metric-chip-label">{m.label}</span>
                          <span className="metric-chip-val">{m.value}</span>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Tabular Data */}
                  {msg.data && msg.dataType === 'table' && Array.isArray(msg.data) && (
                    <div className="message-table-wrapper">
                      <table className="custom-table message-table">
                        <thead>
                          <tr>
                            {Object.keys(msg.data[0]).map((key) => (
                              <th key={key}>{key.replace(/_/g, ' ').toUpperCase()}</th>
                            ))}
                          </tr>
                        </thead>
                        <tbody>
                          {msg.data.map((row, i) => (
                            <tr key={i}>
                              {Object.values(row).map((val, j) => (
                                <td key={j}>{typeof val === 'number' ? val.toLocaleString() : val}</td>
                              ))}
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}

                  {/* Action Buttons */}
                  {msg.suggested_actions && msg.suggested_actions.length > 0 && (
                    <div className="message-actions-row">
                      {msg.suggested_actions.map((act, i) => (
                        <button
                          key={i}
                          className="action-btn-pill"
                          onClick={() => handlePillClick(act.label, act.route)}
                        >
                          {act.label}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
                <span className="message-time">{msg.timestamp}</span>
              </div>
            </div>
          ))}

          {loading && (
            <div className="chat-message-row message-copilot">
              <div className="message-avatar"><Bot size={18} /></div>
              <div className="message-bubble-wrapper">
                <div className="message-bubble typing-bubble">
                  <Sparkles size={16} className="typing-icon" />
                  <span>AI Business Employee is querying database context & running tools...</span>
                </div>
              </div>
            </div>
          )}

          <div ref={chatBottomRef} />
        </div>

        {/* Quick Prompt Pills Shortcut Bar */}
        <div className="quick-prompts-bar">
          <span className="prompts-title">Sample Prompts (English & Tanglish):</span>
          <div className="pills-scroll-row">
            <button className="prompt-pill" onClick={() => handleSendMessage('How much did I sell today?')}>
              📊 How much did I sell today?
            </button>
            <button className="prompt-pill" onClick={() => handleSendMessage('innaiku sales evlo?')}>
              💬 innaiku sales evlo?
            </button>
            <button className="prompt-pill" onClick={() => handleSendMessage('Who owes me money?')}>
              👥 Who owes me money?
            </button>
            <button className="prompt-pill" onClick={() => handleSendMessage('Which items are low in stock?')}>
              📦 Low stock items
            </button>
            <button className="prompt-pill" onClick={() => handleSendMessage('What is my profit this month?')}>
              💰 Profit & Margin
            </button>
            <button className="prompt-pill" onClick={() => handleSendMessage('Send payment reminders to overdue customers')}>
              ⚡ Send Reminders
            </button>
          </div>
        </div>

        {/* Chat Input Bar with Pulsating Audio Ring */}
        <div className="chat-input-bar">
          <div className="voice-btn-wrapper">
            {isListening && <div className="pulse-ring voice-ring" />}
            <button
              type="button"
              className={`voice-btn ${isListening ? 'voice-btn-active' : ''}`}
              onClick={toggleVoiceRecognition}
              title={isListening ? 'Listening...' : 'Speak Question'}
            >
              {isListening ? <MicOff size={18} /> : <Mic size={18} />}
            </button>
          </div>

          <input
            type="text"
            placeholder="Ask AI Copilot (e.g. 'How much did I sell today?' or 'innaiku sales evlo?')..."
            value={inputPrompt}
            onChange={(e) => setInputPrompt(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
            className="chat-input"
          />

          <Button
            variant="primary"
            size="md"
            icon={Send}
            isLoading={loading}
            disabled={!inputPrompt.trim()}
            onClick={() => handleSendMessage()}
          >
            Send
          </Button>
        </div>
      </Card>
    </div>
  );
}
