import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Mic, MicOff, Volume2, VolumeX, X, Sparkles, Globe } from 'lucide-react';
import { useLanguage } from '../../contexts/LanguageContext';
import apiService from '../../services/api';
import './VoiceWidget.css';

export default function VoiceWidget() {
  const navigate = useNavigate();
  const { language } = useLanguage();

  const [isOpen, setIsOpen] = useState(false);
  const [isListening, setIsListening] = useState(false);
  const [isMuted, setIsMuted] = useState(false);
  const [transcript, setTranscript] = useState('');
  const [responseText, setResponseText] = useState('');
  const [processing, setProcessing] = useState(false);

  const recognitionRef = useRef(null);

  const isTamil = language === 'ta';

  // Initialize Speech Recognition Browser API
  useEffect(() => {
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      const recognition = new SpeechRecognition();
      recognition.lang = isTamil ? 'ta-IN' : 'en-US';
      recognition.interimResults = true;
      recognition.continuous = false;

      recognition.onstart = () => {
        setIsListening(true);
        setTranscript('');
        setResponseText(isTamil ? 'கேட்கிறேன்... உங்கள் கட்டளையை பேசுங்கள்.' : 'Listening... Speak your command now.');
      };

      recognition.onresult = (event) => {
        let currentTranscript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
          currentTranscript += event.results[i][0].transcript;
        }
        setTranscript(currentTranscript);
      };

      recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        setIsListening(false);
        setResponseText(isTamil ? 'குரல் அறிவதில் பிழை. மீண்டும் முயற்சிக்கவும்.' : 'Speech recognition error. Please try again.');
      };

      recognition.onend = () => {
        setIsListening(false);
      };

      recognitionRef.current = recognition;
    }
  }, [isTamil]);

  // Text-to-Speech (TTS) Voice Synthesis Readout
  const speakText = (text) => {
    if (isMuted || !('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel(); // Stop any active speech

    const utterance = new SpeechSynthesisUtterance(text.replace(/\*/g, ''));
    utterance.lang = isTamil ? 'ta-IN' : 'en-US';
    utterance.rate = 0.95;
    utterance.pitch = 1.0;

    // Try finding Tamil voice if available in browser
    if (isTamil) {
      const voices = window.speechSynthesis.getVoices();
      const taVoice = voices.find((v) => v.lang.includes('ta'));
      if (taVoice) {
        utterance.voice = taVoice;
      }
    }

    window.speechSynthesis.speak(utterance);
  };

  // Toggle Voice Modal & Start Recognition
  const handleOpenWidget = () => {
    setIsOpen(true);
    setTranscript('');
    setResponseText(
      isTamil
        ? 'மைக்ரோஃபோனை அழுத்தி உங்கள் வியாபார கட்டளையை பேசுங்கள்.'
        : 'Press the microphone and speak your business command.'
    );
  };

  const handleCloseWidget = () => {
    if (recognitionRef.current && isListening) {
      recognitionRef.current.stop();
    }
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    setIsOpen(false);
  };

  const startListening = () => {
    if (!recognitionRef.current) {
      alert('Voice recognition is not supported in this browser. Please use Chrome or Edge.');
      return;
    }
    if (isListening) {
      recognitionRef.current.stop();
    } else {
      recognitionRef.current.start();
    }
  };

  // Process Completed Spoken Command
  const handleProcessCommand = async (spokenText) => {
    const finalQuery = spokenText || transcript;
    if (!finalQuery.trim() || processing) return;

    setProcessing(true);
    setResponseText(isTamil ? 'குரல் கட்டளையை செயல்படுத்துகிறது...' : 'Processing voice command...');

    try {
      const res = await apiService.voice.sendCommand(finalQuery, language);
      if (res.success && res.data) {
        const spoken = res.data.spoken_response;
        setResponseText(spoken);
        speakText(spoken);

        // Auto-navigate if route target returned
        if (res.data.target_route) {
          setTimeout(() => {
            navigate(res.data.target_route);
            setIsOpen(false);
          }, 1600);
        }
      }
    } catch (err) {
      setResponseText(isTamil ? 'குரல் கட்டளையை செயல்படுத்த முடியவில்லை.' : 'Could not process voice command.');
    } finally {
      setProcessing(false);
    }
  };

  // Trigger command processing when speech stops and transcript is captured
  useEffect(() => {
    if (!isListening && transcript.trim().length >= 2 && !processing) {
      handleProcessCommand(transcript);
    }
  }, [isListening]);

  return (
    <>
      {/* Floating Microphone Trigger Button */}
      <button
        className="floating-voice-btn"
        onClick={handleOpenWidget}
        title={isTamil ? 'குரல் உதவி (பேச அழுத்தவும்)' : 'Voice Commands (Press to Speak)'}
      >
        <Mic size={22} />
        <span className="voice-btn-pulse" />
      </button>

      {/* Voice Modal Overlay */}
      {isOpen && (
        <div className="voice-overlay-backdrop">
          <div className="voice-overlay-modal">
            {/* Header */}
            <div className="voice-modal-header">
              <div className="voice-modal-title">
                <Sparkles size={18} className="text-primary" />
                <span>{isTamil ? 'தமிழ் AI குரல் உதவி' : 'Voice Business Commands'}</span>
                <span className="lang-badge-pill">{isTamil ? 'தமிழ் (ta-IN)' : 'English (en-US)'}</span>
              </div>

              <div className="voice-modal-controls">
                <button className="control-icon-btn" onClick={() => setIsMuted(!isMuted)} title={isMuted ? 'Unmute TTS' : 'Mute TTS'}>
                  {isMuted ? <VolumeX size={16} /> : <Volume2 size={16} />}
                </button>
                <button className="control-icon-btn" onClick={handleCloseWidget}>
                  <X size={18} />
                </button>
              </div>
            </div>

            {/* Pulsating Wave / Mic Container */}
            <div className="voice-wave-section">
              <button
                className={`voice-mic-circle ${isListening ? 'mic-listening' : ''}`}
                onClick={startListening}
              >
                {isListening ? <MicOff size={36} /> : <Mic size={36} />}
              </button>

              <span className="voice-status-label">
                {isListening
                  ? (isTamil ? 'கேட்கிறது...' : 'Listening...')
                  : processing
                  ? (isTamil ? 'செயல்படுத்துகிறது...' : 'Processing...')
                  : (isTamil ? 'பேச அழுத்தவும்' : 'Click to Speak')}
              </span>
            </div>

            {/* Transcript & Response Area */}
            <div className="voice-transcript-box">
              {transcript ? (
                <div className="transcript-live">
                  <span className="transcript-label">{isTamil ? 'நீங்கள் பேசியது:' : 'You said:'}</span>
                  <p className="transcript-text">"{transcript}"</p>
                </div>
              ) : null}

              <div className="response-live">
                <p className="response-text">{responseText}</p>
              </div>
            </div>

            {/* Voice Command Shortcuts Suggestions */}
            <div className="voice-suggestions-row">
              {isTamil ? (
                <>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('பில்லிங் திற')}>
                    "பில்லிங் திற"
                  </button>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('யாரு காசு தரணும்?')}>
                    "யாரு காசு தரணும்?"
                  </button>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('ஸ்டாக் எவ்வளவு இருக்கு?')}>
                    "ஸ்டாக் எவ்வளவு இருக்கு?"
                  </button>
                </>
              ) : (
                <>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('Open billing counter')}>
                    "Open billing counter"
                  </button>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('Who owes me money?')}>
                    "Who owes me money?"
                  </button>
                  <button className="v-suggestion-pill" onClick={() => handleProcessCommand('Show low stock')}>
                    "Show low stock"
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </>
  );
}
